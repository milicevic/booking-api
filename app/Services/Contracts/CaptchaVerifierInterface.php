<?php

namespace App\Services\Contracts;

interface CaptchaVerifierInterface
{
    /** Da li je zaštita od botova uključena (postavljen tajni ključ). */
    public function enabled(): bool;

    /** Proverava token koji je browser dobio od captcha widgeta. */
    public function verify(?string $token, ?string $ip): bool;
}
