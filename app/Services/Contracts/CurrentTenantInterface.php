<?php

namespace App\Services\Contracts;

use App\Models\Tenant;

/**
 * Firma (tenant) za koju se obrađuje trenutni zahtev, prepoznata po adresi sajta.
 */
interface CurrentTenantInterface
{
    public function get(): ?Tenant;

    /** Vraća firmu ili baca izuzetak ako zahtev nema tenant kontekst. */
    public function require(): Tenant;

    public function set(Tenant $tenant): void;

    public function has(): bool;
}
