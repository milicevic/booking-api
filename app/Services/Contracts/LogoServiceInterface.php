<?php

namespace App\Services\Contracts;

use App\Models\Tenant;
use Illuminate\Http\UploadedFile;

interface LogoServiceInterface
{
    /** Smanjuje logo, čuva ga kao WebP i postavlja logo_url firme. */
    public function store(Tenant $tenant, UploadedFile $file): Tenant;

    public function deleteAllFor(Tenant $tenant): void;
}
