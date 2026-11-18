<?php

namespace App\Models;

use App\Enums\ConfirmationMode;
use App\Enums\CustomerContact;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClientProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property ?int $tenant_id
 * @property ?string $phone
 * @property ?string $business_name
 * @property bool $auto_confirm_bookings
 * @property ?ConfirmationMode $confirmation_mode null samo pre prvog snimanja (postavlja ga saving hook)
 * @property ?CustomerContact $customer_contact
 * @property int $reschedule_cutoff_hours
 * @property bool $calendar_invites
 * @property ?Carbon $onboarding_completed_at
 */
class ClientProfile extends Model
{
    /** Podrazumevani rok (u satima pre početka) do kog kupac sme sam da pomeri rezervaciju. */
    public const DEFAULT_RESCHEDULE_CUTOFF_HOURS = 6;

    /** @use HasFactory<ClientProfileFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'phone',
        'business_name',
        'auto_confirm_bookings',
        'customer_contact',
        'confirmation_mode',
        'onboarding_completed_at',
        'reschedule_cutoff_hours',
        'calendar_invites',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_confirm_bookings' => 'boolean',
            'customer_contact' => CustomerContact::class,
            'confirmation_mode' => ConfirmationMode::class,
            'onboarding_completed_at' => 'datetime',
            'reschedule_cutoff_hours' => 'integer',
            'calendar_invites' => 'boolean',
        ];
    }

    /**
     * auto_confirm_bookings (staro polje) i confirmation_mode moraju da se slažu:
     * novi FE šalje režim, stari samo zastavicu.
     */
    protected static function booted(): void
    {
        static::saving(function (ClientProfile $profile): void {
            if ($profile->isDirty('confirmation_mode') && $profile->confirmation_mode !== null) {
                $profile->auto_confirm_bookings = $profile->confirmation_mode === ConfirmationMode::Auto;

                return;
            }

            if ($profile->isDirty('auto_confirm_bookings') || $profile->confirmation_mode === null) {
                $profile->confirmation_mode = match (true) {
                    (bool) $profile->auto_confirm_bookings => ConfirmationMode::Auto,
                    $profile->confirmation_mode === ConfirmationMode::Worker => ConfirmationMode::Worker,
                    default => ConfirmationMode::Owner,
                };
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
