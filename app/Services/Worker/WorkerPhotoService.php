<?php

namespace App\Services\Worker;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Contracts\MediaStorageInterface;
use App\Services\Contracts\WorkerPhotoServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class WorkerPhotoService implements WorkerPhotoServiceInterface
{
    /** Slika se prikazuje kao avatar do ~64px, pa 384px pokriva i retina ekrane. */
    private const SIZE = 384;

    private const QUALITY = 85;

    public function __construct(private readonly MediaStorageInterface $media) {}

    public function store(User $worker, UploadedFile $file): User
    {
        $photo = ImageManager::usingDriver(Driver::class)
            ->decodePath((string) $file->getRealPath())
            ->cover(self::SIZE, self::SIZE)
            ->encode(new WebpEncoder(quality: self::QUALITY, strip: true));

        $this->media->deleteDirectory($this->directoryFor($worker));

        $path = "{$this->directoryFor($worker)}/".Str::random(40).'.webp';
        $photoUrl = $this->media->put($path, $photo->toString());
        $worker->workerProfile()->updateOrCreate(['user_id' => $worker->id], ['photo_url' => $photoUrl]);

        return $worker->load('workerProfile');
    }

    public function delete(User $worker): void
    {
        $this->media->deleteDirectory($this->directoryFor($worker));
        $worker->workerProfile()->update(['photo_url' => null]);
    }

    public function deleteAllFor(Tenant $tenant): void
    {
        $this->media->deleteDirectory("worker-photos/{$tenant->id}");
    }

    private function directoryFor(User $worker): string
    {
        return "worker-photos/{$worker->tenant_id}/{$worker->id}";
    }
}
