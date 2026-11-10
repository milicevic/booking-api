<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Services\Contracts\LogoServiceInterface;
use App\Services\Contracts\MediaStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class LogoService implements LogoServiceInterface
{
    /**
     * Najveća širina/visina sačuvanog loga u pikselima. Logo se prikazuje na 24–32px,
     * pa 512px ostavlja dovoljno prostora za retina ekrane i buduću upotrebu (favicon, mail).
     */
    private const MAX_SIZE = 512;

    private const QUALITY = 85;

    public function __construct(private readonly MediaStorageInterface $media) {}

    public function store(Tenant $tenant, UploadedFile $file): Tenant
    {
        $logo = ImageManager::usingDriver(Driver::class)
            ->decodePath((string) $file->getRealPath())
            ->scaleDown(self::MAX_SIZE, self::MAX_SIZE)
            ->encode(new WebpEncoder(quality: self::QUALITY, strip: true));

        $path = "{$this->directoryFor($tenant)}/".Str::random(40).'.webp';
        $tenant->update(['logo_url' => $this->media->put($path, $logo->toString())]);

        return $tenant;
    }

    public function deleteAllFor(Tenant $tenant): void
    {
        $this->media->deleteDirectory($this->directoryFor($tenant));
    }

    private function directoryFor(Tenant $tenant): string
    {
        return "logos/{$tenant->id}";
    }
}
