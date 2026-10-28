<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BookingFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

class Booking extends Model implements HasLocalePreference
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToTenant, HasFactory, HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'tenant_id',
        'slot_id',
        'service_id',
        'start_time',
        'end_time',
        'customer_name',
        'customer_email',
        'customer_phone',
        'token',
        'status',
        'note',
        'locale',
    ];

    /** Statusi rezervacija koje zauzimaju vreme radnika. */
    public const ACTIVE_STATUSES = ['pending', 'confirmed'];

    /** Stranica na sajtu firme na kojoj kupac vidi i otkazuje rezervaciju. */
    public function manageUrl(): string
    {
        $tenant = $this->tenant ?? $this->slot?->worker?->tenant;

        return $tenant
            ? $tenant->siteUrl("/manage/{$this->token}")
            : route('booking.manage', $this->token);
    }

    /** Kupac dobija obaveštenja na jeziku na kom je zakazao, a ako on nije poznat, na jeziku firme. */
    public function preferredLocale(): ?string
    {
        return $this->locale ?? $this->tenant?->locale;
    }

    public function routeNotificationForMail(): string
    {
        return $this->customer_email ?? '';
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->token = Str::random(64);
        });
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
