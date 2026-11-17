<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Stalni raspored radnika: radni dani (1 = ponedeljak … 7 = nedelja), radno vreme, dužina termina i pauze.
 * Iz njega se termini drže napravljenim WEEKS_AHEAD nedelja unapred (komanda slots:extend-schedules).
 *
 * @property int $id
 * @property ?int $tenant_id
 * @property int $worker_id
 * @property list<int> $weekdays
 * @property string $start_time
 * @property string $end_time
 * @property int $slot_duration
 * @property list<array{start: string, end: string}> $breaks
 * @property ?Carbon $generated_until
 * @property-read User $worker
 * @property-read ?Tenant $tenant
 */
class WorkerSchedule extends Model
{
    use BelongsToTenant;

    /** Koliko nedelja unapred (računajući današnji dan) termini moraju postojati. */
    public const WEEKS_AHEAD = 4;

    protected $fillable = [
        'tenant_id',
        'worker_id',
        'weekdays',
        'start_time',
        'end_time',
        'slot_duration',
        'breaks',
        'generated_until',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'breaks' => 'array',
            'slot_duration' => 'integer',
            'generated_until' => 'date',
        ];
    }

    /** Poslednji dan prozora: danas + WEEKS_AHEAD nedelja − 1 dan. */
    public static function windowEnd(): Carbon
    {
        return today()->addDays(self::WEEKS_AHEAD * 7 - 1);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<Slot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }
}
