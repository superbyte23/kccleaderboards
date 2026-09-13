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

        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
