<?php

namespace App\Services\Tenant;

use App\Enums\DeployStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantTheme;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use App\Notifications\WelcomeClient;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\Contracts\SubdomainGeneratorInterface;
use App\Services\Contracts\TenantRegistrationServiceInterface;
use Illuminate\Support\Facades\DB;

class TenantRegistrationService implements TenantRegistrationServiceInterface
{
    private const TRIAL_DAYS = 7;

    private const DEFAULT_LOCALE = 'sr';

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly ClientRepositoryInterface $clients,
        private readonly SubdomainGeneratorInterface $subdomains,
    ) {}

    public function createWithClient(array $tenantData, array $clientData): array
    {
        return DB::transaction(function () use ($tenantData, $clientData): array {
            $locale = app()->getLocale();

            $tenant = $this->tenants->create([
                'name' => $tenantData['app_name'],
                'subdomain' => $tenantData['subdomain'] ?? $this->subdomains->uniqueFor($tenantData['app_name']),
                'app_name' => $tenantData['app_name'],
                'theme' => TenantTheme::Minimal,
                'subscription_status' => SubscriptionStatus::Trialing,
                'trial_ends_at' => now()->addDays(self::TRIAL_DAYS),
                'deploy_status' => DeployStatus::Deployed,
                'locale' => in_array($locale, Tenant::LOCALES, true) ? $locale : self::DEFAULT_LOCALE,
            ]);

            $client = $this->clients->create([
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'password' => $clientData['password'] ?? null,
                'tenant_id' => $tenant->id,
            ]);

            return [$tenant, $client];
        });
    }

    public function register(array $data): array
    {
        [$tenant, $client] = $this->createWithClient($data, $data);

        // Prijava je moguća tek kad korisnik potvrdi email; mejl dobrodošlice stiže posle potvrde
        $client->notify(new VerifyEmailAddress($tenant));

        return [$tenant, $client];
    }

    public function createBySuperadmin(array $data): Tenant
    {
        [$tenant, $client] = $this->createWithClient($data, $data);

        $client->markEmailAsVerified();
        $client->notify(new WelcomeClient($tenant));

        return $tenant;
    }

    public function registerWithGoogle(string $appName, string $name, string $email, string $googleId): User
    {
        [$tenant, $client] = $this->createWithClient(['app_name' => $appName], [
            'name' => $name,
            'email' => $email,
            'password' => null,
        ]);

        $client->update(['google_id' => $googleId]);
        $client->markEmailAsVerified();
        $client->notify(new WelcomeClient($tenant));

        return $client;
    }
}
