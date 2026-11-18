<?php

namespace App\Services\Contracts;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

interface TenantServiceInterface
{
    /**
     * @return Collection<int, Tenant>
     */
    public function allWithUserCount(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Tenant $tenant, array $attributes): Tenant;

    /**
     * Izgled sajta; prazne (null) vrednosti se ignorišu.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateBranding(Tenant $tenant, array $attributes): Tenant;

    /**
     * @param  array{subdomain?: ?string, custom_domain?: ?string}  $data
     */
    public function requestDeploy(Tenant $tenant, array $data): Tenant;

    public function delete(Tenant $tenant): void;

    /**
     * Web manifest (PWA) za sajt na adresi $origin: naziv i boja firme, ili platforme kada je to glavni domen.
     * Null kada adresa nije ni platforma ni sajt neke firme.
     *
     * @return array<string, mixed>|null
     */
    public function webManifest(string $origin): ?array;
}
