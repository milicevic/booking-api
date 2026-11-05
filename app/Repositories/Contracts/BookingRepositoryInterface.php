<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface BookingRepositoryInterface
{
    /**
     * Rezervacije kod datih radnika, najnovije prve, sa terminom, radnikom i uslugom.
     *
     * @param  BaseCollection<int, int>|list<int>  $workerIds
     * @return Collection<int, Booking>
     */
    public function forWorkers(BaseCollection|array $workerIds): Collection;

    /**
     * @param  list<string>  $with
     */
    public function findByTokenOrFail(string $token, array $with = []): Booking;

    /**
     * Rezervacija koja još zauzima vreme (na čekanju ili potvrđena).
     *
     * @param  list<string>  $with
     */
    public function findActiveByTokenOrFail(string $token, array $with = []): Booking;

    /**
     * Zauzeti intervali radnika tog dana, u minutima od ponoći, sortirani po početku.
     *
     * @return list<array{0: int, 1: int}>
     */
    public function activeIntervalsForWorkerOnDate(int $workerId, string $date): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Booking;
}
