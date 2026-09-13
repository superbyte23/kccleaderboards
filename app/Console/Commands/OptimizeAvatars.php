<?php

namespace App\Console\Commands;

use App\Actions\OptimizeAvatar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class OptimizeAvatars extends Command
{
    protected $signature = 'avatars:optimize';

    protected $description = 'Re-encode existing team avatars to 256x256 WebP and update DB paths';

    public function handle(): int
    {
        $disk = Storage::disk('public');

        $files = collect($disk->files('avatars'))
            ->filter(fn (string $p): bool => preg_match('/\.(jpe?g|png|gif)$/i', $p) === 1)
            ->sort();

        if ($files->isEmpty()) {
            $this->warn('No legacy avatars found in storage/app/public/avatars.');

            return self::SUCCESS;
        }

        $manager = new ImageManager(new Driver);

        $saved = 0;
        $errors = 0;
        $bytesBefore = 0;
        $bytesAfter = 0;

        foreach ($files as $file) {
            $from = $disk->path($file);
            $to = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file);

            $oldSize = filesize($from);
            $bytesBefore += $oldSize;

            try {
                $image = $manager->decode($from);
                $image->cover(OptimizeAvatar::SIZE, OptimizeAvatar::SIZE);
                $encoded = $image->encodeUsingFormat(Format::WEBP, quality: OptimizeAvatar::QUALITY);
                $payload = (string) $encoded;

                DB::table('teams')->where('avatar', $file)->update(['avatar' => $to]);

                $disk->put($to, $payload);
                OptimizeAvatar::mirror($to, $payload);
                OptimizeAvatar::delete($file);

                $bytesAfter += strlen($payload);
                $saved++;

                $this->line(sprintf('  %s  [%s -> %s]', $file, $this->humanSize($oldSize), $this->humanSize(strlen($payload))));
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  Failed {$file}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done: {$saved} optimized, {$errors} failed.");
        $this->info('Size: '.$this->humanSize($bytesBefore).' -> '.$this->humanSize($bytesAfter));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? round($bytes / (1024 * 1024), 1).' MB'
            : round($bytes / 1024).' KB';
    }
}
