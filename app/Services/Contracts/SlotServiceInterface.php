<?php

namespace App\Services\Contracts;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SlotServiceInterface
{
    /**
     * @param  array{date?: ?string, worker_id?: int|string|null, service_id?: int|string|null}  $filters
     * @return Collection<int, Slot>
     */
    public function availableForPublic(array $filters): Collection;

    /**
     * Vremena za zakazivanje kod radnika, prema trajanju izabrane usluge.
     *
     * @return list<array{slot_id: int, date: string, start_time: string, end_time: string}>
     */
    public function bookableTimes(int $workerId, ?int $serviceId, ?string $date): array;

    /**
     * @return Collection<int, Slot>
     */
    public function listFor(User $user, ?int $clientId = null): Collection;

    /**
     * @param  array{worker_id: int, date: string, start_time: string, end_time: string}  $data
     */
    public function create(array $data): Slot;

    public function delete(Slot $slot): void;
}
