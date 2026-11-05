<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateBrandingRequest;
use App\Http\Requests\Tenant\UploadLogoRequest;
use App\Http\Resources\TenantBrandingResource;
use App\Http\Resources\TenantConfigResource;
use App\Services\Contracts\CurrentTenantInterface;
use App\Services\Contracts\LogoServiceInterface;
use App\Services\Contracts\TenantServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class TenantController extends Controller
{
    public function __construct(private readonly CurrentTenantInterface $currentTenant) {}

    /** Javna podešavanja sajta; null kada zahtev nije sa sajta neke firme. */
    public function config(): JsonResponse|TenantConfigResource
    {
        $tenant = $this->currentTenant->get();

        return $tenant ? TenantConfigResource::make($tenant) : response()->json(null);
    }

    public function updateBranding(UpdateBrandingRequest $request, TenantServiceInterface $tenants): TenantBrandingResource
    {
        return TenantBrandingResource::make($tenants->updateBranding($this->currentTenant->require(), $request->validated()));
    }

    public function uploadLogo(UploadLogoRequest $request, LogoServiceInterface $logos): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('logo');
        $tenant = $logos->store($this->currentTenant->require(), $file);

        return response()->json(['logo_url' => $tenant->logo_url]);
    }
}
