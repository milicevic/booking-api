<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Koje kontakt podatke kupac mora da ostavi pri zakazivanju (podešava klijent).
 */
enum CustomerContact: string
{
    use HasValues;

    /** Bar jedno: email ili telefon (podrazumevano) */
    case EmailOrPhone = 'email_or_phone';
    case Both = 'both';
    case Phone = 'phone';
    case Email = 'email';

    /** Ključ prevoda greške ako kontakt kupca ne ispunjava pravilo, inače null. */
    public function violation(?string $email, ?string $phone): ?string
    {
        $hasEmail = filled($email);
        $hasPhone = filled($phone);

        return match ($this) {
            self::EmailOrPhone => $hasEmail || $hasPhone ? null : 'messages.contact_email_or_phone_required',
            self::Both => $hasEmail && $hasPhone ? null : 'messages.contact_both_required',
            self::Phone => $hasPhone ? null : 'messages.contact_phone_required',
            self::Email => $hasEmail ? null : 'messages.contact_email_required',
        };
    }
}
