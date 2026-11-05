<?php

namespace App\Services\Slot;

use App\Models\Slot;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\SlotRepositoryInterface;
use App\Services\Contracts\SlotAvailabilityServiceInterface;
use Illuminate\Support\Collection;

/**
 * Termini određuju radno vreme radnika; uzastopni termini se spajaju u jedan blok.
 * Nude se počeci termina i trenuci kada se završava neka aktivna rezervacija,
 * ako od tog trenutka ima dovoljno slobodnog vremena za uslugu (npr. posle usluge
 * od 40 minuta u 08:00 nudi se 08:40 za uslugu od 20 minuta).
 * Bez trajanja (radnik bez usluga) nude se samo celi slobodni termini.
 */
class SlotAvailabilityService implements SlotAvailabilityServiceInterface
{
    public function __construct(
        private readonly SlotRepositoryInterface $slots,
        private readonly BookingRepositoryInterface $bookings,
    ) {}

    public function bookableTimes(int $workerId, ?int $durationMinutes, ?string $date = null): array
    {
        $slotsByDay = $this->slots->upcomingForWorker($workerId, $date)
            ->groupBy(fn (Slot $slot): string => $slot->date->toDateString());

        $times = [];

        foreach ($slotsByDay as $day => $daySlots) {
            $day = (string) $day;
            $taken = $this->bookings->activeIntervalsForWorkerOnDate($workerId, $day);
            $free = $this->subtractIntervals($this->mergedIntervals($daySlots), $taken);
            $nowMinutes = $day === today()->toDateString() ? Slot::toMinutes(now()->format('H:i:s')) : -1;

            $candidates = $daySlots->map(fn (Slot $slot): int => Slot::toMinutes($slot->start_time))
                ->merge(array_column($taken, 1))
                ->unique()
                ->sort()
                ->values();

            foreach ($candidates as $start) {
                if ($start <= $nowMinutes) {
                    continue;
                }

                /** @var Slot|null $slot */
                $slot = $daySlots->first(fn (Slot $slot): bool => Slot::toMinutes($slot->start_time) <= $start && $start < Slot::toMinutes($slot->end_time));

                if (! $slot) {
                    continue;
                }

                if ($durationMinutes === null) {
                    // Bez usluge rezervacija zauzima ceo termin, pa nudimo samo njegov početak
                    if ($start !== Slot::toMinutes($slot->start_time)) {
                        continue;
                    }
                    $end = Slot::toMinutes($slot->end_time);
                } else {
                    $end = $start + $durationMinutes;
                }

                $fits = collect($free)->contains(fn (array $interval): bool => $interval[0] <= $start && $end <= $interval[1]);

                if ($fits) {
                    $times[] = [
                        'slot_id' => $slot->id,
                        'date' => $day,
                        'start_time' => Slot::fromMinutes($start),
                        'end_time' => Slot::fromMinutes($end),
                    ];
                }
            }
        }

        return $times;
    }

    public function refreshAvailability(int $workerId, string $date): void
    {
        $taken = $this->bookings->activeIntervalsForWorkerOnDate($workerId, $date);

        $this->slots->forWorkerOnDate($workerId, $date)
            ->each(function (Slot $slot) use ($taken): void {
                $interval = [Slot::toMinutes($slot->start_time), Slot::toMinutes($slot->end_time)];
                $slot->update(['is_available' => $this->subtractIntervals([$interval], $taken) !== []]);
            });
    }

    /**
     * @param  Collection<int, Slot>  $slots
     * @return list<array{0: int, 1: int}>
     */
    private function mergedIntervals(Collection $slots): array
    {
        $merged = [];

        foreach ($slots as $slot) {
            [$start, $end] = [Slot::toMinutes($slot->start_time), Slot::toMinutes($slot->end_time)];
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
    private function subtractIntervals(array $intervals, array $taken): array
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
}
