<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/** Akcija koju vlasnik firme bira linkom iz mejla o novoj rezervaciji. */
enum BookingLinkAction: string
{
    use HasValues;

    case Confirm = 'confirm';
    case Reject = 'reject';

    /** Prevedena poruka o uspehu akcije. */
    public function successMessage(): string
    {
        return match ($this) {
            self::Confirm => __('messages.booking_confirmed'),
            self::Reject => __('messages.booking_rejected'),
        };
    }
}
