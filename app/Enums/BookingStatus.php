<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum BookingStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /** Rezervacije u ovim statusima zauzimaju vreme radnika i mogu da se otkažu. */
    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Confirmed];
    }
}
