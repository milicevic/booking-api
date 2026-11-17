<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Ko potvrđuje nove rezervacije (podešava vlasnik firme).
 */
enum ConfirmationMode: string
{
    use HasValues;

    /** Vlasnik potvrđuje sve rezervacije (podrazumevano) */
    case Owner = 'owner';

    /** Radnik potvrđuje svoje rezervacije; vlasnik i dalje sme */
    case Worker = 'worker';

    /** Rezervacija je odmah potvrđena */
    case Auto = 'auto';
}
