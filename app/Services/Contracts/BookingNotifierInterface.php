<?php

namespace App\Services\Contracts;

use App\Models\Booking;

/**
 * Obaveštava kupca, vlasnika firme i radnika (režim potvrde "worker") o promenama rezervacije.
 */
interface BookingNotifierInterface
{
    /** Nova rezervacija: na čekanju (zahtev ide onome ko potvrđuje) ili odmah potvrđena (režim "auto"). */
    public function created(Booking $booking): void;

    public function confirmed(Booking $booking): void;

    public function rejected(Booking $booking): void;

    public function cancelled(Booking $booking): void;

    /** Kupac je otkazao rezervaciju: obaveštava se vlasnik (mejl, admin, push). */
    public function cancelledByCustomer(Booking $booking): void;

    /** Vlasnik ili radnik je pomerio rezervaciju na drugi termin. */
    public function rescheduled(Booking $booking): void;

    /** Kupac je sam pomerio rezervaciju (na čekanju ponovo ide na potvrdu, u režimu "auto" ostaje potvrđena). */
    public function rescheduledByCustomer(Booking $booking): void;
}
