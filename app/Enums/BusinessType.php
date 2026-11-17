<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Vrsta delatnosti firme; frontend po njoj predlaže usluge (booking-fe/src/lib/onboarding.ts).
 */
enum BusinessType: string
{
    use HasValues;

    case Salon = 'salon';
    case Barber = 'barber';
    case Dentist = 'dentist';
    case Mechanic = 'mechanic';
    case Beauty = 'beauty';
    case Other = 'other';
}
