<?php

namespace App\Services\Contracts;

/**
 * Računa slobodna vremena radnika i održava zastavicu is_available na terminima.
 */
interface SlotAvailabilityServiceInterface
{
    /**
     * Vremena u koja može da počne rezervacija kod radnika. Blokirani termini se ne nude.
     * $exceptBookingId: rezervacija koja se pomera ne zauzima svoje trenutno vreme.
     *
     * @return list<array{slot_id: int, worker_id: int, date: string, start_time: string, end_time: string}>
     */
    public function bookableTimes(int $workerId, int $durationMinutes, ?string $date = null, ?int $exceptBookingId = null): array;

    /**
     * Da li usluga može da počne u $startTime (H:i:s) tog dana: početak je u nekom (neblokiranom) terminu,
     * nije prošao, a od njega ima dovoljno slobodnog vremena. Za pomeranje iz kalendara, gde vreme nije
     * ograničeno na ponuđene početke. Vraća vreme kao bookableTimes ili null.
     *
     * @return array{slot_id: int, worker_id: int, date: string, start_time: string, end_time: string}|null
     */
    public function timeAt(int $workerId, int $durationMinutes, string $date, string $startTime, ?int $exceptBookingId = null): ?array;

    /** Termin je slobodan dok u njemu ima bar malo vremena koje nije zauzeto rezervacijom. */
    public function refreshAvailability(int $workerId, string $date): void;
}
