<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\PushSubscription;

class Tenant extends Model
{
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

    /** Jezici na kojima firma može da prima obaveštenja. */
    public const LOCALES = ['sr', 'en'];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function clientProfiles(): HasMany
    {
        return $this->hasMany(ClientProfile::class);
    }

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
     * Pravi slobodan poddomen od naziva firme (npr. "Moj Salon" → "moj-salon", "moj-salon-1"...).
     */
    public static function uniqueSubdomainFor(string $appName): string
    {
        // Naziv bez slova i brojeva (npr. samo emoji) ne daje slug, pa koristimo podrazumevani
        $base = Str::slug($appName) ?: 'moj-salon';
        $subdomain = $base;
        $counter = 1;

        while (static::where('subdomain', $subdomain)->exists()) {
            $subdomain = "{$base}-{$counter}";
            $counter++;
        }

        return $subdomain;
    }

    /**
     * Kreira firmu i njenog vlasnika (klijenta). Sajt je odmah dostupan na poddomenu,
     * jer ResolveTenant prepoznaje svaki poddomen iz baze.
     *
     * @param  array{app_name: string, subdomain?: string|null}  $tenantData
     * @param  array{name: string, email: string, password: string}  $clientData
     * @return array{0: Tenant, 1: User}
     */
    public static function createWithClient(array $tenantData, array $clientData): array
    {
        return DB::transaction(function () use ($tenantData, $clientData): array {
            $tenant = static::create([
                'name' => $tenantData['app_name'],
                'subdomain' => $tenantData['subdomain'] ?? static::uniqueSubdomainFor($tenantData['app_name']),
                'app_name' => $tenantData['app_name'],
                'theme' => 'minimal',
                'subscription_status' => 'trialing',
                'trial_ends_at' => now()->addDays(7),
                'deploy_status' => 'deployed',
                'locale' => in_array(app()->getLocale(), static::LOCALES) ? app()->getLocale() : 'sr',
            ]);

            /** @var User $user */
            $user = User::withoutGlobalScopes()->create([
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'password' => $clientData['password'],
                'role' => 'client',
                'tenant_id' => $tenant->id,
            ]);

            return [$tenant, $user];
        });
    }

    /**
     * Briše firmu sa svim njenim podacima: korisnicima, terminima, rezervacijama, uslugama i logom.
     *
     * Korisnici se brišu eksplicitno, jer bi ih FK (nullOnDelete) inače ostavio bez tenanta,
     * a korisnik bez tenanta ima pristup kao platformski admin.
     */
    public function deleteWithData(): void
    {
        DB::transaction(function (): void {
            $userIds = User::withoutGlobalScopes()->where('tenant_id', $this->id)->pluck('id');
            $slotIds = Slot::withoutGlobalScopes()
                ->where('tenant_id', $this->id)
                ->orWhereIn('worker_id', $userIds)
                ->pluck('id');
            $bookingIds = Booking::withoutGlobalScopes()
                ->where('tenant_id', $this->id)
                ->orWhereIn('slot_id', $slotIds)
                ->pluck('id');

            PushSubscription::query()
                ->where(fn ($query) => $query->where('subscribable_type', (new User)->getMorphClass())->whereIn('subscribable_id', $userIds))
                ->orWhere(fn ($query) => $query->where('subscribable_type', (new Booking)->getMorphClass())->whereIn('subscribable_id', $bookingIds))
                ->delete();
            DatabaseNotification::query()
                ->where('notifiable_type', (new User)->getMorphClass())
                ->whereIn('notifiable_id', $userIds)
                ->delete();
            PersonalAccessToken::query()
                ->where('tokenable_type', (new User)->getMorphClass())
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            Booking::withoutGlobalScopes()->whereIn('id', $bookingIds)->delete();
            Slot::withoutGlobalScopes()->whereIn('id', $slotIds)->delete();
            User::withoutGlobalScopes()->whereIn('id', $userIds)->delete();

            $this->delete();
        });

        Storage::disk('public')->deleteDirectory("logos/{$this->id}");
    }
}
