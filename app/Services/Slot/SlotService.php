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

    public function bookableTimes(int $workerId, ?int $serviceId, ?string $date): array
    {
        $duration = null;

        if ($serviceId !== null) {
            $service = $this->services->findActiveForWorker($serviceId, $workerId)
                ?? throw DomainException::unprocessable(__('messages.service_unavailable_for_worker'));
            $duration = $service->duration_minutes;
        }

        return $this->availability->bookableTimes($workerId, $duration, $date);
    }

    public function listFor(User $user, ?int $clientId = null): Collection
    {
        return $this->slots->forWorkers($this->visibleWorkers->workerIdsFor($user, $clientId));
    }

    public function create(array $data): Slot
    {
        return $this->slots->create($data);
    }

    public function delete(Slot $slot): void
    {
        $slot->delete();
    }
}
