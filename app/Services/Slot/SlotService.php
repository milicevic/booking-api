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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SlotService implements SlotServiceInterface
{
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

    public function listFor(User $user, ?int $clientId = null): Collection
    {
        return $this->slots->forWorkers($this->visibleWorkers->workerIdsFor($user, $clientId));
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

    public function delete(Slot $slot): void
    {
        $slot->delete();
    }
}
