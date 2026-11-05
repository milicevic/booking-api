<?php

namespace App\Services\Contracts;

use App\Models\Tenant;
use App\Models\User;

interface TenantRegistrationServiceInterface
{
    /**
     * Kreira firmu i njenog vlasnika; sajt je odmah dostupan na poddomenu.
     *
     * @param  array{app_name: string, subdomain?: string|null}  $tenantData
     * @param  array{name: string, email: string, password: string|null}  $clientData
     * @return array{0: Tenant, 1: User}
     */
    public function createWithClient(array $tenantData, array $clientData): array;

    /**
     * Samostalna registracija lozinkom; prijava je moguća tek posle potvrde emaila.
     *
     * @param  array{name: string, email: string, password: string, app_name: string, subdomain?: string|null}  $data
     * @return array{0: Tenant, 1: User}
     */
    public function register(array $data): array;

    /**
     * Firma koju ručno otvara superadmin; nalog ne prolazi potvrdu emaila.
     *
     * @param  array{name: string, email: string, password: string, app_name: string, subdomain?: string|null}  $data
     */
    public function createBySuperadmin(array $data): Tenant;

    /** Registracija Google nalogom; email je već potvrdio Google. */
    public function registerWithGoogle(string $appName, string $name, string $email, string $googleId): User;
}
