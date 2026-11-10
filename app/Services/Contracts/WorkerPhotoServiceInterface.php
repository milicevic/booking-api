<?php

namespace App\Services\Contracts;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface WorkerPhotoServiceInterface
{
    /** Iseca kvadratnu sliku, čuva je kao WebP i postavlja photo_url radnika (stara slika se briše). */
    public function store(User $worker, UploadedFile $file): User;

    public function delete(User $worker): void;

    public function deleteAllFor(Tenant $tenant): void;
}
