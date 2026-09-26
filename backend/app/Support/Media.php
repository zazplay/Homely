<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * The disk for user media (listing photos, avatars, covers).
 * Locally "public" (storage/app/public); in production "s3" — hosts like Render
 * wipe the local disk on every deploy. Set with MEDIA_DISK.
 */
final class Media
{
    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    public static function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public static function url(?string $path): ?string
    {
        return $path === null ? null : self::disk()->url($path);
    }
}
