<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateDeployStatusRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Http\Requests\Admin\UpdateThemeRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\Contracts\TenantRegistrationServiceInterface;
use App\Services\Contracts\TenantServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Upravljanje firmama (samo admin, middleware CheckAdmin): lista, ručno otvaranje i brisanje, pretplata,
 * status deploy-a i tema. Plaćanja nema, pa admin ručno menja pretplatu.
 */
class AdminTenantController extends Controller
{
    public function __construct(private readonly TenantServiceInterface $tenants) {}

    public function tenants(): AnonymousResourceCollection
    {
        return TenantResource::collection($this->tenants->allWithUserCount());
    }

    /** Ručno kreira firmu sa vlasnikom; sajt je odmah dostupan, kao i posle registracije. */
    public function storeTenant(StoreTenantRequest $request, TenantRegistrationServiceInterface $registration): JsonResponse
    {
        /** @var array{name: string, email: string, password: string, app_name: string, subdomain?: string|null} $data */
        $data = $request->validated();

        return TenantResource::make($registration->createByAdmin($data)->load('users'))
            ->response()
            ->setStatusCode(201);
    }

    public function showTenant(Tenant $tenant): TenantResource
    {
        return TenantResource::make($tenant->load('users'));
    }

    public function updateSubscription(UpdateSubscriptionRequest $request, Tenant $tenant): TenantResource
    {
        return TenantResource::make($this->tenants->update($tenant, $request->validated()));
    }

    public function updateDeployStatus(UpdateDeployStatusRequest $request, Tenant $tenant): TenantResource
    {
        return TenantResource::make($this->tenants->update($tenant, $request->validated()));
    }

    public function updateTheme(UpdateThemeRequest $request, Tenant $tenant): TenantResource
    {
        return TenantResource::make($this->tenants->update($tenant, $request->validated()));
    }

    public function destroyTenant(Tenant $tenant): JsonResponse
    {
        $this->tenants->delete($tenant);

        return response()->json(null, 204);
    }
}
