<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Vremena u koja može da počne rezervacija kod radnika.
     *
     * Termini određuju radno vreme radnika; uzastopni termini se spajaju u jedan blok.
     * Nude se počeci termina i trenuci kada se završava neka aktivna rezervacija,
     * ako od tog trenutka ima dovoljno slobodnog vremena za uslugu (npr. posle usluge
     * od 40 minuta u 08:00 nudi se 08:40 za uslugu od 20 minuta).
     * Bez trajanja (radnik bez usluga) nude se samo celi slobodni termini.
     *
     * @return list<array{slot_id: int, date: string, start_time: string, end_time: string}>
     */
    public static function bookableTimes(int $workerId, ?int $durationMinutes, ?string $date = null): array
    {
        $slots = static::where('worker_id', $workerId)
            ->whereDate('date', '>=', today())
            ->when($date, fn ($query) => $query->whereDate('date', $date))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Slot $slot) => $slot->date->toDateString());

        $times = [];

        foreach ($slots as $day => $daySlots) {
            $bookings = static::activeBookingIntervals($workerId, $day);
            $free = static::subtractIntervals(static::mergedIntervals($daySlots), $bookings);
            $nowMinutes = $day === today()->toDateString() ? static::toMinutes(now()->format('H:i:s')) : -1;

            $candidates = $daySlots->map(fn (Slot $slot) => static::toMinutes($slot->start_time))
                ->merge(array_column($bookings, 1))
                ->unique()
                ->sort()
                ->values();

            foreach ($candidates as $start) {
                if ($start <= $nowMinutes) {
                    continue;
                }

                $slot = $daySlots->first(fn (Slot $slot) => static::toMinutes($slot->start_time) <= $start && $start < static::toMinutes($slot->end_time));

                if (! $slot) {
                    continue;
                }

                if ($durationMinutes === null) {
                    // Bez usluge rezervacija zauzima ceo termin, pa nudimo samo njegov početak
                    if ($start !== static::toMinutes($slot->start_time)) {
                        continue;
                    }
                    $end = static::toMinutes($slot->end_time);
                } else {
                    $end = $start + $durationMinutes;
                }

                $fits = collect($free)->contains(fn (array $interval) => $interval[0] <= $start && $end <= $interval[1]);

                if ($fits) {
                    $times[] = [
                        'slot_id' => $slot->id,
                        'date' => $day,
                        'start_time' => static::fromMinutes($start),
                        'end_time' => static::fromMinutes($end),
                    ];
                }
            }
        }

        return $times;
    }

    /**
     * Termin je slobodan dok u njemu ima bar malo vremena koje nije zauzeto rezervacijom.
     */
    public static function refreshAvailability(int $workerId, string $date): void
    {
        $bookings = static::activeBookingIntervals($workerId, $date);

        static::where('worker_id', $workerId)
            ->whereDate('date', $date)
            ->get()
            ->each(function (Slot $slot) use ($bookings): void {
                $interval = [static::toMinutes($slot->start_time), static::toMinutes($slot->end_time)];
                $slot->update(['is_available' => static::subtractIntervals([$interval], $bookings) !== []]);
            });
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private static function activeBookingIntervals(int $workerId, string $date): array
    {
        return Booking::query()
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->whereHas('slot', fn ($query) => $query->where('worker_id', $workerId)->whereDate('date', $date))
            ->get(['start_time', 'end_time'])
            ->map(fn (Booking $booking) => [static::toMinutes($booking->start_time), static::toMinutes($booking->end_time)])
            ->sortBy(0)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Slot>  $slots
     * @return list<array{0: int, 1: int}>
     */
    private static function mergedIntervals(Collection $slots): array
    {
        $merged = [];

        foreach ($slots as $slot) {
            [$start, $end] = [static::toMinutes($slot->start_time), static::toMinutes($slot->end_time)];
            $last = count($merged) - 1;

            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        return $merged;
    }

    /**
     * @param  list<array{0: int, 1: int}>  $intervals
     * @param  list<array{0: int, 1: int}>  $taken
     * @return list<array{0: int, 1: int}>
     */
    private static function subtractIntervals(array $intervals, array $taken): array
    {
        foreach ($taken as [$takenStart, $takenEnd]) {
            $remaining = [];

            foreach ($intervals as [$start, $end]) {
                if ($takenEnd <= $start || $takenStart >= $end) {
                    $remaining[] = [$start, $end];

                    continue;
                }
                if ($takenStart > $start) {
                    $remaining[] = [$start, $takenStart];
                }
                if ($takenEnd < $end) {
                    $remaining[] = [$takenEnd, $end];
                }
            }

            $intervals = $remaining;
        }

        return $intervals;
    }

    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }

    public static function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
