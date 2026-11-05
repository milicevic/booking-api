<?php

namespace App\Repositories\Contracts;

use App\Models\Slot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface SlotRepositoryInterface
{
    /**
     * Slobodni budući termini za javni prikaz.
     *
     * @param  array{date?: ?string, worker_id?: int|string|null, service_id?: int|string|null}  $filters
     * @return Collection<int, Slot>
     */
    public function availableForPublic(array $filters): Collection;

    /**
     * @param  BaseCollection<int, int>|list<int>  $workerIds
     * @return Collection<int, Slot>
     */
    public function forWorkers(BaseCollection|array $workerIds): Collection;

    /**
     * Termini radnika od danas nadalje (ili samo za dati dan), sortirani po datumu i početku.
     *
     * @return Collection<int, Slot>
     */
    public function upcomingForWorker(int $workerId, ?string $date = null): Collection;

    /**
     * @return Collection<int, Slot>
     */
    public function forWorkerOnDate(int $workerId, string $date): Collection;

    /** Zaključava sve termine radnika tog dana do kraja transakcije. */
    public function lockWorkerDay(int $workerId, string $date): void;

    public function findOrFail(int $id): Slot;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Slot;
}
