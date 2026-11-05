<?php

namespace App\Services\Contracts;

use App\Models\Booking;

/**
 * Obaveštava kupca i vlasnika firme o promenama rezervacije.
 */
interface BookingNotifierInterface
{
    /** Nova rezervacija: na čekanju ili odmah potvrđena (auto-confirm). */
    public function created(Booking $booking): void;

    public function confirmed(Booking $booking): void;

    public function rejected(Booking $booking): void;

    public function cancelled(Booking $booking): void;
}
