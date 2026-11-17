<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\DeployRequest;
use App\Http\Requests\Client\UpdateSettingsRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Services\Contracts\ClientServiceInterface;
use App\Services\Contracts\CurrentTenantInterface;
use App\Services\Contracts\TenantServiceInterface;

class ClientController extends Controller
{
    /** Ažurira podešavanja klijentskog profila */
    public function updateSettings(UpdateSettingsRequest $request, ClientServiceInterface $clients): UserResource
    {
        return UserResource::make($clients->updateSettings($request->user(), $request->settings()));
    }

    /** Klijent šalje zahtev za deploy (subdomena ili custom domena) */
    public function deployRequest(DeployRequest $request, TenantServiceInterface $tenants, CurrentTenantInterface $currentTenant): TenantResource
    {
        /** @var array{subdomain?: ?string, custom_domain?: ?string} $data */
        $data = $request->validated();

        return TenantResource::make($tenants->requestDeploy($currentTenant->require(), $data));
    }
}
