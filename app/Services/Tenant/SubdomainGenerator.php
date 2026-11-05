<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\Contracts\SubdomainGeneratorInterface;
use Illuminate\Support\Str;

class SubdomainGenerator implements SubdomainGeneratorInterface
{
    /** Naziv bez slova i brojeva (npr. samo emoji) ne daje slug, pa se koristi podrazumevani. */
    private const FALLBACK = 'moj-salon';

    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    public function uniqueFor(string $appName): string
    {
        $base = Str::slug($appName) ?: self::FALLBACK;
        $subdomain = $base;
        $counter = 1;

        while (in_array($subdomain, Tenant::RESERVED_SUBDOMAINS, true) || $this->tenants->subdomainExists($subdomain)) {
            $subdomain = "{$base}-{$counter}";
            $counter++;
        }

        return $subdomain;
    }
}
