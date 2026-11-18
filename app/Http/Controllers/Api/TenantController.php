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
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class TenantController extends Controller
{
    public function __construct(private readonly CurrentTenantInterface $currentTenant) {}

    /**
     * Web manifest za instalaciju sajta kao aplikacije (PWA). Frontend je jedan build za sve firme, pa
     * naziv i boju daje API; adresa sajta stiže kao ?origin=https://salon.slotres.click.
     */
    public function manifest(Request $request, TenantServiceInterface $tenants): JsonResponse
    {
        $origin = $request->query('origin');
        $valid = is_string($origin) && filter_var($origin, FILTER_VALIDATE_URL) && in_array(parse_url($origin, PHP_URL_SCHEME), ['https', 'http'], true);
        $manifest = $valid ? $tenants->webManifest($origin) : null;

        if (! $manifest) {
            return response()->json(['message' => __('http.404')], 404);
        }

        return response()->json($manifest, 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600']);
    }

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
