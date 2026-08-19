<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Single entry point for member uploaded files.
 *
 * The disk was hardcoded to "public" in every controller, which stores files
 * under storage/app/public on the local filesystem. Hosts with an ephemeral
 * filesystem lose that directory on every restart and every deploy, taking
 * every avatar and every review image with it. Naming the disk once, from
 * configuration, lets the same code write to object storage in production and
 * to the local disk in development.
 */
class Media
{
    public static function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.media'));
    }

    /**
     * Store an upload and return the path to keep in the database.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, config('filesystems.media'));
    }

    /**
     * Delete a stored file, tolerating a null or already missing path.
     */
    public static function delete(?string $path): void
    {
        if ($path) {
            self::disk()->delete($path);
        }
    }

    /**
     * The public address of a stored file.
     */
    public static function url(?string $path): ?string
    {
        return $path ? self::disk()->url($path) : null;
    }
}
