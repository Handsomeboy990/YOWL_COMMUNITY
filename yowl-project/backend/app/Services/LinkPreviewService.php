<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the metadata a page publishes about itself, so a cited link can be
 * shown as a card rather than as a bare address.
 *
 * Everything here treats the remote page as hostile: only http and https are
 * followed, private and loopback addresses are refused so the server cannot be
 * used to probe the network it sits in, the response is size capped, and every
 * extracted field is length limited before it reaches the database.
 */
class LinkPreviewService
{
    private const TIMEOUT = 6;

    private const MAX_BYTES = 512 * 1024;

    private const CACHE_HOURS = 24;

    /**
     * @return array{title: ?string, description: ?string, image: ?string, site_name: ?string}|null
     */
    public function fetch(string $url): ?array
    {
        if (! $this->isFetchable($url)) {
            return null;
        }

        // Deux membres citant la meme adresse ne la telechargent pas deux fois.
        return Cache::remember(
            'link-preview.'.sha1($url),
            now()->addHours(self::CACHE_HOURS),
            fn () => $this->download($url)
        );
    }

    /**
     * @return array{title: ?string, description: ?string, image: ?string, site_name: ?string}|null
     */
    private function download(string $url): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'YOWL-linkpreview/1.0 (+https://yowl.community)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->withOptions(['allow_redirects' => ['max' => 3, 'strict' => true]])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $type = $response->header('Content-Type');
            if ($type && ! str_contains(strtolower($type), 'html')) {
                return null;
            }

            $html = substr($response->body(), 0, self::MAX_BYTES);
            $meta = $this->parse($html, $url);

            // Une carte sans titre n'apporte rien de plus qu'un lien nu.
            return $meta['title'] ? $meta : null;
        } catch (\Throwable $e) {
            Log::info('Link preview failed for '.$url.': '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array{title: ?string, description: ?string, image: ?string, site_name: ?string}
     */
    private function parse(string $html, string $url): array
    {
        return [
            'title' => $this->clean(
                $this->meta($html, 'og:title')
                ?? $this->meta($html, 'twitter:title')
                ?? $this->titleTag($html),
                160
            ),
            'description' => $this->clean(
                $this->meta($html, 'og:description')
                ?? $this->meta($html, 'twitter:description')
                ?? $this->meta($html, 'description'),
                300
            ),
            'image' => $this->absolute(
                $this->meta($html, 'og:image') ?? $this->meta($html, 'twitter:image'),
                $url
            ),
            'site_name' => $this->clean($this->meta($html, 'og:site_name'), 60)
                ?? parse_url($url, PHP_URL_HOST),
        ];
    }

    private function meta(string $html, string $name): ?string
    {
        // property= et name= sont tous deux utilises selon les sites, et
        // l'ordre des attributs varie.
        $escaped = preg_quote($name, '/');
        $patterns = [
            '/<meta[^>]+(?:property|name)\s*=\s*["\']'.$escaped.'["\'][^>]*content\s*=\s*["\']([^"\']*)["\']/i',
            '/<meta[^>]+content\s*=\s*["\']([^"\']*)["\'][^>]*(?:property|name)\s*=\s*["\']'.$escaped.'["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function titleTag(string $html): ?string
    {
        return preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m) ? $m[1] : null;
    }

    private function clean(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $value = preg_replace('/\s+/u', ' ', $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /**
     * Turn a relative image reference into an absolute one, and refuse
     * anything that is not an http address.
     */
    private function absolute(?string $image, string $base): ?string
    {
        if (! $image) {
            return null;
        }

        $image = trim($image);

        if (str_starts_with($image, '//')) {
            $image = (parse_url($base, PHP_URL_SCHEME) ?: 'https').':'.$image;
        } elseif (str_starts_with($image, '/')) {
            $parts = parse_url($base);
            $image = $parts['scheme'].'://'.$parts['host'].$image;
        }

        $scheme = parse_url($image, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true) ? mb_substr($image, 0, 2048) : null;
    }

    /**
     * Refuse anything that is not a public http address.
     *
     * Without this the endpoint would happily fetch http://localhost or a
     * private address on behalf of whoever pasted it, which turns the server
     * into a probe of its own network.
     */
    private function isFetchable(string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
