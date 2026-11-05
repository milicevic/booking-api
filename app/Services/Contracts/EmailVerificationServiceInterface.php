<?php

namespace App\Services\Contracts;

interface EmailVerificationServiceInterface
{
    /**
     * Potvrđuje email i vraća adresu stranice za prijavu (?verified=1 ili ?verified=invalid).
     */
    public function verify(int $userId, string $hash, bool $hasValidSignature): string;

    /** Ponovo šalje mejl za potvrdu, ako nalog postoji i nije potvrđen. */
    public function resend(string $email): void;
}
