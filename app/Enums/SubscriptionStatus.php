<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SubscriptionStatus: string
{
    use HasValues;

    case Trialing = 'trialing';
    case Active = 'active';
    case Expired = 'expired';
    case Canceled = 'canceled';

    /** Firma sa isteklom ili otkazanom pretplatom ne može da koristi sajt. */
    public function blocksAccess(): bool
    {
        return in_array($this, [self::Expired, self::Canceled], true);
    }
}
