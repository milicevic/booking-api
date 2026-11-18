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

    public function webManifest(string $origin): ?array
    {
        $hostname = (string) parse_url($origin, PHP_URL_HOST);
        $tenant = Tenant::isPlatformHost($hostname) ? null : $this->tenants->findByHost($hostname);

        if (! $tenant && ! Tenant::isPlatformHost($hostname)) {
            return null;
        }

        $name = $tenant ? ($tenant->app_name ?: $tenant->name) : (string) config('app.name');
        $color = $tenant?->primary_color && preg_match('/^#[0-9a-f]{6}$/i', $tenant->primary_color)
            ? $tenant->primary_color
            : '#4f46e5';
        $origin = rtrim($origin, '/');

        // Adrese su apsolutne: manifest stiže sa API domena, a aplikacija je sajt firme
        return [
            'name' => $name,
            'short_name' => mb_strimwidth($name, 0, 12),
            'id' => $origin.'/',
            'start_url' => $origin.'/',
            'scope' => $origin.'/',
            'display' => 'standalone',
            'background_color' => '#f9fafb',
            'theme_color' => $color,
            'lang' => $tenant->locale ?? 'sr',
            'icons' => [
                ['src' => $origin.'/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $origin.'/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => $origin.'/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];
    }
}
