<?php

namespace App\Services\Slot;

use App\Exceptions\DomainException;
use App\Models\Slot;
use App\Models\User;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\SlotRepositoryInterface;
use App\Services\Contracts\SlotAvailabilityServiceInterface;
use App\Services\Contracts\SlotServiceInterface;
use App\Services\Contracts\VisibleWorkersResolverInterface;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SlotService implements SlotServiceInterface
{
    /** Gornja granica za jedan grupni zahtev (oko 3 meseca punog radnog vremena u terminima od 15 minuta). */
    public const BULK_MAX_SLOTS = 3000;

    public function __construct(
        private readonly SlotRepositoryInterface $slots,
        private readonly ServiceRepositoryInterface $services,
        private readonly SlotAvailabilityServiceInterface $availability,
        private readonly VisibleWorkersResolverInterface $visibleWorkers,
    ) {}

    public function availableForPublic(array $filters): Collection
    {
        return $this->slots->availableForPublic($filters);
    }

    public function bookableTimes(?int $workerId, int $serviceId, ?string $date): array
    {
        if ($workerId === null) {
            return $this->bookableTimesForService($serviceId, $date);
        }

        $service = $this->services->findActiveForWorker($serviceId, $workerId)
            ?? throw DomainException::unprocessable(__('messages.service_unavailable_for_worker'));

        return $this->availability->bookableTimes($workerId, $service->duration_minutes, $date);
    }

    /**
     * Vremena svih radnika koji rade uslugu, poređana po datumu, vremenu i radniku.
     *
     * @return list<array{slot_id: int, worker_id: int, date: string, start_time: string, end_time: string}>
     */
    private function bookableTimesForService(int $serviceId, ?string $date): array
    {
        $service = $this->services->findActiveWithWorkers($serviceId)
            ?? throw DomainException::unprocessable(__('messages.service_unavailable'));

        return $service->workers
            ->flatMap(fn (User $worker): array => $this->availability->bookableTimes($worker->id, $service->duration_minutes, $date))
            ->sortBy([['date', 'asc'], ['start_time', 'asc'], ['worker_id', 'asc']])
            ->values()
            ->all();
    }

    public function listFor(User $user, ?int $clientId = null, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->slots->forWorkers($this->visibleWorkers->workerIdsFor($user, $clientId), $filters, $perPage);
    }

    /**
     * Radnik ne može imati dva termina koja se preklapaju (generator i ručno dodavanje bi inače pravili duplikate).
     */
    public function create(array $data): Slot
    {
        return DB::transaction(function () use ($data): Slot {
            $date = (new Carbon($data['date']))->toDateString();
            $start = Carbon::parse($data['start_time'])->format('H:i:s');
            $end = Carbon::parse($data['end_time'])->format('H:i:s');

            $workerId = (int) $data['worker_id'];

            $this->slots->lockWorkerDay($workerId, $date);

            if ($this->slots->overlapsForWorker($workerId, $date, $start, $end)) {
                throw DomainException::unprocessable(__('messages.slot_overlaps'));
            }

            return $this->slots->create($data);
        });
    }

    public function createMany(array $data): array
    {
        $workerId = (int) $data['worker_id'];
        $candidates = $this->bulkCandidates($data);

        if (count($candidates) > self::BULK_MAX_SLOTS) {
            throw DomainException::unprocessable(__('messages.slots_bulk_too_many', ['max' => self::BULK_MAX_SLOTS]));
        }

        return DB::transaction(function () use ($workerId, $data, $candidates): array {
            $existing = $this->slots->lockWorkerRange($workerId, $data['date_from'], $data['date_to'])
                ->groupBy(fn (Slot $slot): string => $slot->date->toDateString());

            $created = 0;
            $skipped = 0;

            foreach ($candidates as [$date, $start, $end]) {
                // Dodirivanje nije preklapanje: 09:00–10:00 i 10:00–11:00 mogu zajedno
                $overlaps = ($existing[$date] ?? collect())->contains(
                    fn (Slot $slot): bool => Slot::toMinutes($slot->start_time) < $end && Slot::toMinutes($slot->end_time) > $start
                );

                if ($overlaps) {
                    $skipped++;

                    continue;
                }

                $this->slots->create([
                    // Komanda nema tenant u kontekstu, pa ga raspored prosleđuje sam
                    'tenant_id' => $data['tenant_id'] ?? null,
                    'worker_schedule_id' => $data['worker_schedule_id'] ?? null,
                    'worker_id' => $workerId,
                    'date' => $date,
                    'start_time' => Slot::fromMinutes($start),
                    'end_time' => Slot::fromMinutes($end),
                ]);
                $created++;
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /**
     * Isto pravilo kao generator na frontendu (lib/slotGenerator.ts): termini od početka radnog vremena
     * redom, bez onih koji seku pauzu; današnji termini koji su već počeli se ne prave.
     *
     * @param  array{date_from: string, date_to: string, weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>}  $data
     * @return list<array{0: string, 1: int, 2: int}> [datum, početak, kraj] u minutima od ponoći
     */
    private function bulkCandidates(array $data): array
    {
        $workStart = Slot::toMinutes($data['start']);
        $workEnd = Slot::toMinutes($data['end']);
        $duration = (int) $data['slot_duration'];
        $weekdays = array_map('intval', $data['weekdays']);
        $breaks = array_map(
            fn (array $break): array => [Slot::toMinutes($break['start']), Slot::toMinutes($break['end'])],
            $data['breaks'] ?? []
        );
        $today = today()->toDateString();
        $nowMinutes = Slot::toMinutes(now()->format('H:i'));

        $candidates = [];

        foreach (CarbonPeriod::create($data['date_from'], $data['date_to']) as $day) {
            if (! in_array($day->dayOfWeekIso, $weekdays, true)) {
                continue;
            }

            $date = $day->toDateString();

            for ($start = $workStart; $start + $duration <= $workEnd; $start += $duration) {
                $end = $start + $duration;

                if ($date === $today && $start <= $nowMinutes) {
                    continue;
                }

                foreach ($breaks as [$breakStart, $breakEnd]) {
                    if ($start < $breakEnd && $end > $breakStart) {
                        continue 2;
                    }
                }

                $candidates[] = [$date, $start, $end];

                if (count($candidates) > self::BULK_MAX_SLOTS) {
                    return $candidates;
                }
            }
        }

        return $candidates;
    }

    public function setBlocked(Slot $slot, bool $blocked): Slot
    {
        $slot->update(['is_blocked' => $blocked]);

        return $slot;
    }

    public function delete(Slot $slot): void
    {
        $slot->delete();
    }
}
