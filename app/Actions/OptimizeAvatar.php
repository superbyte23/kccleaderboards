<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * OptimizeAvatar — resizes + re-encodes team avatar uploads.
 *
 * Avatars render at most at 112px (md:size-28 podium) yet users upload
 * multi-MB originals. We square-crop to a fixed size and store as WebP,
 * cutting response size by ~95% while staying sharp on retina displays.
 *
 * Returns the relative path on the public disk (e.g. avatars/<name>.webp),
 * ready to store directly in the teams.avatar column.
 */
class OptimizeAvatar
{
    public const SIZE = 256;

    public const QUALITY = 82;

    public static function run(UploadedFile|string $source, string $dir = 'avatars'): string
    {
        $manager = new ImageManager(new Driver);

        $image = $manager->decode(is_string($source) ? $source : $source->getPathname());
        $image->cover(self::SIZE, self::SIZE);

        $encoded = $image->encodeUsingFormat(Format::WEBP, quality: self::QUALITY);

        $path = $dir.'/'.Str::random(40).'.webp';

        $payload = (string) $encoded;

        Storage::disk('public')->put($path, $payload);
        self::mirror($path, $payload);

        return $path;
    }

    /**
     * Delete an avatar from the public disk and its docroot mirror, if any.
     */
    public static function delete(string $path): void
    {
        Storage::disk('public')->delete($path);

        $web = public_path('storage/'.$path);
        if (is_file($web)) {
            @unlink($web);
        }
    }

    /**
     * Mirror a file into public/storage when it is a real directory.
     *
     * asset('storage/...') only resolves when public/storage exists as the
     * standard storage:link symlink. Some shared hosts (InfinityFree free
     * tier) disable symlink(), so the deploy ends up with a real directory
     * instead — new uploads would 404 without this copy. No-op when the
     * symlink is in place, and self-creates the directory if missing.
     */
    public static function mirror(string $path, string $bytes): void
    {
        $webDir = public_path('storage');

        if (is_link($webDir)) {
            return;
        }

        $dest = $webDir.'/'.$path;

        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        file_put_contents($dest, $bytes);
    }
}
