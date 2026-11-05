<?php

namespace App\Models;

use App\Enums\DeployStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantTheme;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property ?string $subdomain
 * @property ?string $custom_domain
 * @property ?string $primary_color
 * @property ?string $secondary_color
 * @property ?string $logo_url
 * @property ?string $app_name
 * @property ?TenantTheme $theme
 * @property ?SubscriptionStatus $subscription_status
 * @property ?DeployStatus $deploy_status
 * @property ?Carbon $trial_ends_at
 * @property ?Carbon $subscription_ends_at
 * @property ?string $currency
 * @property ?string $locale
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'custom_domain',
        'primary_color',
        'secondary_color',
        'logo_url',
        'app_name',
        'theme',
        'trial_ends_at',
        'subscription_status',
        'subscription_ends_at',
        'deploy_status',
        'currency',
        'locale',
    ];

    /** Valute koje firma može da izabere za cene usluga. */
    public const CURRENCIES = ['RSD', 'EUR', 'BAM', 'MKD', 'USD', 'GBP', 'CHF'];

    /** Poddomeni koje koristi sama platforma, pa ih firma ne može dobiti. */
    public const RESERVED_SUBDOMAINS = ['www', 'api', 'admin', 'app', 'mail', 'static', 'cdn'];

    /** Jezici na kojima firma može da prima obaveštenja. */
    public const LOCALES = ['sr', 'en'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'subscription_status' => SubscriptionStatus::class,
            'deploy_status' => DeployStatus::class,
            'theme' => TenantTheme::class,
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Slot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<ClientProfile, $this>
     */
    public function clientProfiles(): HasMany
    {
        return $this->hasMany(ClientProfile::class);
    }

    /**
     * @return HasMany<WorkerProfile, $this>
     */
    public function workerProfiles(): HasMany
    {
        return $this->hasMany(WorkerProfile::class);
    }

    /**
     * Adresa sajta firme (custom domen ili poddomen platforme), npr. https://salon-ana.slotres.click/manage/abc.
     */
    public function siteUrl(string $path = ''): string
    {
        if ($this->custom_domain) {
            return "https://{$this->custom_domain}{$path}";
        }

        $port = config('app.tenant_site_port') ? ':'.config('app.tenant_site_port') : '';

        return "https://{$this->subdomain}.".config('app.platform_host')."{$port}{$path}";
    }

    /**
     * Da li je adresa glavni domen platforme (npr. slotres.click ili www.slotres.click), a ne sajt neke firme.
     */
    public static function isPlatformHost(string $hostname): bool
    {
        $platformHost = config('app.platform_host');

        return $hostname === $platformHost || $hostname === "www.{$platformHost}";
    }
}
