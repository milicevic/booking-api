<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\BelongsToTenant;
use App\Notifications\ResetPasswordLink;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property string $name
 * @property ?string $email
 * @property ?string $password
 * @property UserRole $role
 * @property ?int $client_id
 * @property ?int $tenant_id
 * @property bool $can_edit_slots
 * @property bool $is_suspended
 * @property ?string $invite_token
 * @property ?string $google_id
 * @property ?Carbon $email_verified_at
 * @property-read ?Tenant $tenant
 * @property-read ?User $client
 * @property-read ?ClientProfile $clientProfile
 * @property-read ?WorkerProfile $workerProfile
 */
#[Fillable(['name', 'email', 'password', 'role', 'client_id', 'can_edit_slots', 'is_suspended', 'tenant_id', 'invite_token', 'google_id'])]
#[Hidden(['password', 'google_id', 'invite_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => UserRole::class,
            'password' => 'hashed',
            'can_edit_slots' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    /** Vlasnik i radnici firme dobijaju obaveštenja na jeziku firme, bez obzira na to ko je pokrenuo slanje. */
    public function preferredLocale(): ?string
    {
        return $this->tenant?->locale;
    }

    /**
     * Razlog zbog kog korisnik ne sme da se prijavi, ili null ako sme.
     * Platformski korisnici (superadmin, admin) se prijavljuju sa bilo koje adrese, ostali samo na sajtu svoje firme.
     *
     * @return 'suspended'|'email_not_verified'|'wrong_tenant'|null
     */
    public function loginDeniedReason(?Tenant $currentTenant): ?string
    {
        if ($this->is_suspended) {
            return 'suspended';
        }

        if (! $this->hasVerifiedEmail()) {
            return 'email_not_verified';
        }

        if (! $this->role->isPlatformUser() && $currentTenant && $this->tenant_id !== $currentTenant->id) {
            return 'wrong_tenant';
        }

        return null;
    }

    /** Link za novu lozinku vodi na sajt firme (vidi ResetPasswordLink), ne na Laravel-ovu podrazumevanu rutu. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLink($token));
    }

    /**
     * Workers belonging to this client user.
     *
     * @return HasMany<User, $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(User::class, 'client_id')->where('role', UserRole::Worker);
    }

    /**
     * The client user this worker belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return HasOne<ClientProfile, $this>
     */
    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    /**
     * @return HasOne<WorkerProfile, $this>
     */
    public function workerProfile(): HasOne
    {
        return $this->hasOne(WorkerProfile::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'worker_services', 'worker_id', 'service_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
