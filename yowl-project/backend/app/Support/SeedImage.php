<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the photographs used by the demonstration seed, once, and stores
 * them on the media disk.
 *
 * The download happens here, on the server, one image at a time. An earlier
 * version handed the remote addresses to the browser, which then asked for
 * twenty at once and got rate limited, so most of the feed showed broken
 * images. Downloading server side removes that entirely: the browser only ever
 * sees local files, and the seed runs against the same storage path production
 * uses.
 *
 * A file already on disk is never fetched again, so re-running the seeder is
 * cheap and works without a network.
 */
class SeedImage
{
    private const SOURCE = 'https://picsum.photos/seed/%s/1200/800';

    private const AVATAR_SOURCE = 'https://i.pravatar.cc/400?u=%s';

    private const TIMEOUT = 20;

    /**
     * Download an illustration and return the path stored in the database.
     *
     * Returns null when the image cannot be obtained, so the caller publishes
     * without one rather than storing a path pointing at nothing.
     */
    public static function illustration(string $seed): ?string
    {
        return self::fetch('seed/'.$seed.'.jpg', sprintf(self::SOURCE, 'yowl-'.$seed));
    }

    public static function avatar(string $seed): ?string
    {
        return self::fetch('seed/avatars/'.$seed.'.jpg', sprintf(self::AVATAR_SOURCE, 'yowl-'.$seed));
    }

    private static function fetch(string $path, string $url): ?string
    {
        $disk = Media::disk();

        if ($disk->exists($path)) {
            return $path;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'YOWL-seeder/1.0'])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Seed image refused: '.$url.' ('.$response->status().')');

                return null;
            }

            $body = $response->body();
            // Une reponse vide ou une page d'erreur HTML ne fait pas une image.
            if (strlen($body) < 1024 || ! self::looksLikeImage($body)) {
                Log::warning('Seed image is not an image: '.$url);

                return null;
            }

            $disk->put($path, $body);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Seed image failed: '.$url.' '.$e->getMessage());

            return null;
        }
    }

    /**
     * Check the magic bytes rather than trusting the content type header.
     */
    private static function looksLikeImage(string $body): bool
    {
        $jpeg = "\xFF\xD8\xFF";
        $png = "\x89PNG\r\n\x1a\n";
        $webp = 'RIFF';

        return str_starts_with($body, $jpeg)
            || str_starts_with($body, $png)
            || (str_starts_with($body, $webp) && str_contains(substr($body, 0, 16), 'WEBP'));
    }
}
