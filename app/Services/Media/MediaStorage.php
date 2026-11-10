<?php

namespace App\Services\Media;

use App\Services\Contracts\MediaStorageInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Slike koje postavljaju korisnici. Disk se bira sa MEDIA_DISK: lokalno "public",
 * a na produkciji bucket (npr. Cloudflare R2 preko "s3" diska), jer se disk servera briše pri deploy-u.
 */
class MediaStorage implements MediaStorageInterface
{
    private const LOCAL_DISK = 'public';

    public function __construct(private readonly FilesystemFactory $filesystem) {}

    public function put(string $path, string $contents): string
    {
        $this->disk()->put($path, $contents);

        return $this->url($path);
    }

    public function deleteDirectory(string $directory): void
    {
        $this->disk()->deleteDirectory($directory);
    }

    /**
     * Lokalni disk vraća relativnu putanju (/storage/...), jer je frontend na drugom hostu
     * i prosleđuje /storage na API; bucket vraća punu javnu adresu.
     */
    public function url(string $path): string
    {
        return $this->diskName() === self::LOCAL_DISK ? '/storage/'.$path : $this->disk()->url($path);
    }

    private function diskName(): string
    {
        return (string) config('filesystems.media_disk', self::LOCAL_DISK);
    }

    private function disk(): Filesystem
    {
        return $this->filesystem->disk($this->diskName());
    }
}
