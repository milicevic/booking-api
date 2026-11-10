<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\WorkerProfile;
use App\Services\Contracts\MediaStorageInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Prebacuje slike sačuvane na lokalnom disku (/storage/...) na disk iz MEDIA_DISK (npr. R2)
 * i upisuje nove adrese. Pokreće se jednom, posle podešavanja bucket-a.
 */
class MoveMediaToDisk extends Command
{
    private const LOCAL_PREFIX = '/storage/';

    protected $signature = 'media:move-to-disk';

    protected $description = 'Move uploaded logos and worker photos from the local disk to MEDIA_DISK';

    public function handle(MediaStorageInterface $media, FilesystemFactory $filesystem): int
    {
        if (config('filesystems.media_disk') === 'public') {
            $this->warn('MEDIA_DISK is "public"; set it to the bucket disk (e.g. r2) first.');

            return self::FAILURE;
        }

        $local = $filesystem->disk('public');
        $moved = 0;
        $missing = 0;

        $move = function (?string $url) use ($media, $local, &$moved, &$missing): ?string {
            if (! $url || ! str_starts_with($url, self::LOCAL_PREFIX)) {
                return $url;
            }

            $path = substr($url, strlen(self::LOCAL_PREFIX));
            $contents = $local->get($path);

            if ($contents === null) {
                $missing++;

                return null;
            }

            $moved++;

            return $media->put($path, $contents);
        };

        Tenant::query()->where('logo_url', 'like', self::LOCAL_PREFIX.'%')->each(
            fn (Tenant $tenant) => $tenant->update(['logo_url' => $move($tenant->logo_url)])
        );

        WorkerProfile::withoutGlobalScopes()->where('photo_url', 'like', self::LOCAL_PREFIX.'%')->each(
            fn (WorkerProfile $profile) => $profile->update(['photo_url' => $move($profile->photo_url)])
        );

        $this->info("Moved {$moved} file(s).");

        if ($missing > 0) {
            $this->warn("{$missing} file(s) were missing on the local disk; their URLs were cleared.");
        }

        return self::SUCCESS;
    }
}
