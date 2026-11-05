<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Services\Contracts\CurrentTenantInterface;
use Illuminate\Contracts\Container\Container;
use LogicException;

/**
 * Čuva tekuću firmu u containeru pod ključem "current_tenant", pa je vide i globalni scope modela.
 */
class CurrentTenant implements CurrentTenantInterface
{
    private const CONTAINER_KEY = 'current_tenant';

    public function __construct(private readonly Container $container) {}

    public function get(): ?Tenant
    {
        if (! $this->container->bound(self::CONTAINER_KEY)) {
            return null;
        }

        $tenant = $this->container->make(self::CONTAINER_KEY);

        return $tenant instanceof Tenant ? $tenant : null;
    }

    public function require(): Tenant
    {
        return $this->get() ?? throw new LogicException('The request has no tenant context.');
    }

    public function set(Tenant $tenant): void
    {
        $this->container->instance(self::CONTAINER_KEY, $tenant);
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }
}
