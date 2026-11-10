<?php

namespace App\Services\Tenant;

use App\Enums\DeployStatus;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\Contracts\LogoServiceInterface;
use App\Services\Contracts\TenantServiceInterface;
use App\Services\Contracts\WorkerPhotoServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class TenantService implements TenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly LogoServiceInterface $logos,
        private readonly WorkerPhotoServiceInterface $workerPhotos,
    ) {}

    public function allWithUserCount(): Collection
    {
        return $this->tenants->allWithUserCount();
    }

    public function update(Tenant $tenant, array $attributes): Tenant
    {
        $tenant->update($attributes);

        return $tenant->fresh() ?? $tenant;
    }

    public function updateBranding(Tenant $tenant, array $attributes): Tenant
    {
        $tenant->update(array_filter($attributes, fn (mixed $value): bool => $value !== null));

        return $tenant;
    }

    public function requestDeploy(Tenant $tenant, array $data): Tenant
    {
        $updates = ['deploy_status' => DeployStatus::PendingDeploy];

        if (! empty($data['subdomain'])) {
            $updates['subdomain'] = $data['subdomain'];
        }

        if (! empty($data['custom_domain'])) {
            $updates['custom_domain'] = $data['custom_domain'];
        }

        return $this->update($tenant, $updates);
    }

    public function delete(Tenant $tenant): void
    {
        $this->tenants->deleteWithData($tenant);
        $this->logos->deleteAllFor($tenant);
        $this->workerPhotos->deleteAllFor($tenant);
    }
}
