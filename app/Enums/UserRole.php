<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserRole: string
{
    use HasValues;

    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Client = 'client';
    case Worker = 'worker';

    /** Platformski korisnici se prijavljuju sa bilo koje adrese, ostali samo na sajtu svoje firme. */
    public function isPlatformUser(): bool
    {
        return in_array($this, [self::Superadmin, self::Admin], true);
    }

    /** Uloge koje upravljaju katalogom usluga firme. */
    public function managesServices(): bool
    {
        return in_array($this, [self::Client, self::Admin], true);
    }
}
