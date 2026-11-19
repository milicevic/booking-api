<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface BookingRepositoryInterface
{
    /**
     * Rezervacije kod datih radnika, najnovije prve, sa terminom, radnikom i uslugom.
     *
     * Filteri: opseg datuma termina, radnik, status; sa $perPage straničeno.
     *
     * @param  BaseCollection<int, int>|list<int>  $workerIds
     * @param  array{from?: ?string, to?: ?string, worker_id?: ?int, status?: ?string}  $filters
     * @return Collection<int, Booking>|LengthAwarePaginator<int, Booking>
     */
    public function forWorkers(BaseCollection|array $workerIds, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator;

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
    public function activeIntervalsForWorkerOnDate(int $workerId, string $date, ?int $exceptBookingId = null): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Booking;

    /** Aktivne rezervacije kupca (isti telefon ili email) koje još nisu počele, u trenutnoj firmi. */
    public function countActiveUpcomingForCustomer(?string $phone, ?string $email): int;

    /** Rezervacije kupca (isti telefon ili email) napravljene posle $since, u trenutnoj firmi. */
    public function countCreatedSinceForCustomer(?string $phone, ?string $email, \DateTimeInterface $since): int;

    /**
     * Aktivne rezervacije kupca koje još nisu počele (za otkazivanje pri blokiranju).
     *
     * @return Collection<int, Booking>
     */
    public function activeUpcomingForCustomer(?string $phone, ?string $email): Collection;

    /**
     * Rezervacije koje čekaju potvrdu, u svim firmama (komanda bookings:expire-pending), sa podešavanjima vlasnika.
     *
     * @return Collection<int, Booking>
     */
    public function pendingAcrossTenants(): Collection;
}
