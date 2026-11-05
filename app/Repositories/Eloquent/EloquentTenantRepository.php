<?php

namespace App\Repositories\Eloquent;

use App\Enums\SubscriptionStatus;
use App\Models\Booking;
use App\Models\PersonalAccessToken;
use App\Models\Slot;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\PushSubscription;

class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function findByHost(string $hostname): ?Tenant
    {
        $tenant = Tenant::where('custom_domain', $hostname)->first();

        if ($tenant) {
            return $tenant;
        }

        $platformHost = (string) config('app.platform_host');

        if (! str_ends_with($hostname, '.'.$platformHost)) {
            return null;
        }

        return Tenant::where('subdomain', substr($hostname, 0, -(strlen($platformHost) + 1)))->first();
    }

    public function findForRequest(string $host, ?string $subdomainHeader): ?Tenant
    {
        $tenant = Tenant::where('custom_domain', $host)->first()
            ?? Tenant::where('subdomain', explode('.', $host)[0])->first();

        if (! $tenant && $subdomainHeader) {
            $tenant = Tenant::where('subdomain', $subdomainHeader)->first();
        }

        return $tenant;
    }

    public function subdomainExists(string $subdomain): bool
    {
        return Tenant::where('subdomain', $subdomain)->exists();
    }

    public function allWithUserCount(): Collection
    {
        return Tenant::withCount('users')->orderBy('created_at', 'desc')->get();
    }

    public function create(array $attributes): Tenant
    {
        return Tenant::create($attributes);
    }

    /**
     * Korisnici se brišu eksplicitno, jer bi ih FK (nullOnDelete) inače ostavio bez tenanta,
     * a korisnik bez tenanta ima pristup kao platformski admin.
     */
    public function deleteWithData(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant): void {
            $userIds = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->pluck('id');
            $slotIds = Slot::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->orWhereIn('worker_id', $userIds)
                ->pluck('id');
            $bookingIds = Booking::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
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

            $tenant->delete();
        });
    }

    public function expireOverdueTrials(): int
    {
        return Tenant::where('subscription_status', SubscriptionStatus::Trialing)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update(['subscription_status' => SubscriptionStatus::Expired]);
    }
}
