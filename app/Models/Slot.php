<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $worker_id
 * @property ?int $tenant_id
 * @property Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property bool $is_available
 * @property-read User $worker
 * @property-read ?Booking $booking
 */
class Slot extends Model
{
    /** @use HasFactory<SlotFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'worker_id',
        'date',
        'start_time',
        'end_time',
        'is_available',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * @return HasOne<Booking, $this>
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** "09:30:00" → 570 (minuta od ponoći). */
    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }

    /** 570 → "09:30:00". */
    public static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
