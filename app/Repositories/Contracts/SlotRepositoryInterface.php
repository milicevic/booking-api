<?php

namespace App\Repositories\Contracts;

use App\Models\Slot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface SlotRepositoryInterface
{
    /**
     * Slobodni, neblokirani budući termini za javni prikaz.
     *
     * @param  array{date?: ?string, worker_id?: int|string|null, service_id?: int|string|null}  $filters
     * @return Collection<int, Slot>
     */
    public function availableForPublic(array $filters): Collection;

    /**
     * Termini radnika, opciono u opsegu datuma; sa $perPage straničeno.
     *
     * @param  BaseCollection<int, int>|list<int>  $workerIds
     * @param  array{from?: ?string, to?: ?string, worker_id?: ?int}  $filters
     * @return Collection<int, Slot>|LengthAwarePaginator<int, Slot>
     */
    public function forWorkers(BaseCollection|array $workerIds, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator;

    /**
     * Neblokirani termini radnika od danas nadalje (ili samo za dati dan), sortirani po datumu i početku.
     *
     * @return Collection<int, Slot>
     */
    public function upcomingForWorker(int $workerId, ?string $date = null): Collection;

    /**
     * @return Collection<int, Slot>
     */
    public function forWorkerOnDate(int $workerId, string $date): Collection;

    /**
     * Termini radnika u periodu, zaključani do kraja transakcije.
     *
     * @return Collection<int, Slot>
     */
    public function lockWorkerRange(int $workerId, string $from, string $to): Collection;

    /** Zaključava sve termine radnika tog dana do kraja transakcije. */
    public function lockWorkerDay(int $workerId, string $date): void;

    /** Da li radnik tog dana već ima termin koji se preklapa sa [$start, $end). */
    public function overlapsForWorker(int $workerId, string $date, string $start, string $end): bool;

    /**
     * Briše buduće termine napravljene iz rasporeda koji nemaju nijednu rezervaciju i nisu blokirani
     * (brisanje termina briše i njegove rezervacije, pa se takvi nikad ne diraju). Vraća broj obrisanih.
     */
    public function deleteFreeFutureFromSchedule(int $scheduleId): int;

    public function findOrFail(int $id): Slot;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Slot;
}
