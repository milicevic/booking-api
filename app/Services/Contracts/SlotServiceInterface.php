<?php

namespace App\Services\Contracts;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SlotServiceInterface
{
    /**
     * @param  array{date?: ?string, worker_id?: int|string|null, service_id?: int|string|null}  $filters
     * @return Collection<int, Slot>
     */
    public function availableForPublic(array $filters): Collection;

    /**
     * Vremena za zakazivanje usluge, prema njenom trajanju: kod izabranog radnika,
     * ili (bez radnika) kod svakog radnika koji je radi — isto vreme se tada javlja
     * jednom za svakog slobodnog radnika.
     *
     * @return list<array{slot_id: int, worker_id: int, date: string, start_time: string, end_time: string}>
     */
    public function bookableTimes(?int $workerId, int $serviceId, ?string $date): array;

    /**
     * Termini radnika koje korisnik sme da vidi; filter worker_id ne može da proširi taj skup.
     *
     * @param  array{from?: ?string, to?: ?string, worker_id?: ?int}  $filters
     * @return Collection<int, Slot>|LengthAwarePaginator<int, Slot>
     */
    public function listFor(User $user, ?int $clientId = null, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator;

    /**
     * @param  array{worker_id: int, date: string, start_time: string, end_time: string}  $data
     */
    public function create(array $data): Slot;

    /**
     * Pravi termine za period (radni dani 1 = ponedeljak … 7 = nedelja, radno vreme, dužina, pauze) u jednoj
     * transakciji. Termini koji se preklapaju sa postojećim ili sa pauzom se preskaču; prošla vremena se ne prave.
     *
     * @param  array{worker_id: int, date_from: string, date_to: string, weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>, tenant_id?: ?int, worker_schedule_id?: ?int}  $data
     * @return array{created: int, skipped: int}
     */
    public function createMany(array $data): array;

    /** Blokiran termin se ne nudi kupcima; postojeće rezervacije u njemu ostaju. */
    public function setBlocked(Slot $slot, bool $blocked): Slot;

    public function delete(Slot $slot): void;
}
