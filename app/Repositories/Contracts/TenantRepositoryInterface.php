<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

interface TenantRepositoryInterface
{
    /**
     * Firma kojoj pripada adresa: njen custom domen ili poddomen platforme (npr. salon-ana.slotres.click).
     */
    public function findByHost(string $hostname): ?Tenant;

    /**
     * Firma za dolazni zahtev: custom domen, prvi deo hosta kao poddomen, pa X-Tenant-Subdomain header.
     */
    public function findForRequest(string $host, ?string $subdomainHeader): ?Tenant;

    public function subdomainExists(string $subdomain): bool;

    /**
     * @return Collection<int, Tenant>
     */
    public function allWithUserCount(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Tenant;

    /**
     * Briše firmu sa korisnicima, terminima, rezervacijama, push pretplatama, notifikacijama i tokenima.
     */
    public function deleteWithData(Tenant $tenant): void;

    /** Prebacuje firme čiji je probni period istekao u status expired; vraća broj firmi. */
    public function expireOverdueTrials(): int;
}
