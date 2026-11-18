<?php

namespace App\Services\Contracts;

use App\Models\Booking;

/**
 * Poziv u kalendar za rezervaciju: .ics fajl (Apple/iPhone, Outlook) sa podsetnikom i link za Google Calendar.
 */
interface BookingCalendarInterface
{
    /** Vlasnik firme je uključio pozive u kalendar (client_profiles.calendar_invites, podrazumevano da). */
    public function enabled(Booking $booking): bool;

    /**
     * iCalendar sadržaj. Za otkazanu ili odbijenu rezervaciju to je otkazivanje istog događaja (isti UID),
     * pa ga kalendar uklanja.
     */
    public function ics(Booking $booking): string;

    /** Javni link za preuzimanje .ics fajla (po tokenu, bez prijave). */
    public function icsUrl(Booking $booking): string;

    /** Link koji otvara Google Calendar sa popunjenim događajem (podsetnik određuje sam Google). */
    public function googleUrl(Booking $booking): string;
}
