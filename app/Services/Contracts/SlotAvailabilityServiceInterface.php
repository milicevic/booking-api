<?php

namespace App\Services\Contracts;

/**
 * Računa slobodna vremena radnika i održava zastavicu is_available na terminima.
 */
interface SlotAvailabilityServiceInterface
{
    /**
     * Vremena u koja može da počne rezervacija kod radnika.
     *
     * @return list<array{slot_id: int, date: string, start_time: string, end_time: string}>
     */
    public function bookableTimes(int $workerId, ?int $durationMinutes, ?string $date = null): array;

    /** Termin je slobodan dok u njemu ima bar malo vremena koje nije zauzeto rezervacijom. */
    public function refreshAvailability(int $workerId, string $date): void;
}
