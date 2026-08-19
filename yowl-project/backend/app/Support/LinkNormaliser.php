<?php

namespace App\Support;

/**
 * Reduces an address to what identifies the page, so two members citing the
 * same thing are recognised as citing the same thing.
 *
 * Tracking parameters, the scheme, a www prefix and a trailing slash all
 * change the string without changing the page. Without this, the same article
 * shared twice produces two unrelated discussions.
 */
class LinkNormaliser
{
    /** Paramètres qui suivent la campagne, pas le contenu. */
    private const TRACKING = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'fbclid', 'gclid', 'mc_cid', 'mc_eid', 'igshid', 'ref', 'ref_src',
        '_ga', 'yclid', 'msclkid', 'si',
    ];

    public static function fingerprint(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url(trim($url));
        if (! $parts || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./', '', $host);

        $path = rtrim($parts['path'] ?? '/', '/');
        if ($path === '') {
            $path = '/';
        }

        $query = '';
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);
            foreach (self::TRACKING as $inutile) {
                unset($params[$inutile]);
            }
            ksort($params);
            $query = $params ? '?'.http_build_query($params) : '';
        }

        // Le fragment identifie une position dans la page, pas la page.
        return $host.$path.$query;
    }
}
