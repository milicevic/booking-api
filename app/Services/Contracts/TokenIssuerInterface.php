<?php

namespace App\Services\Contracts;

use App\Models\User;

interface TokenIssuerInterface
{
    /** Izdaje API token za korisnika i vraća ga u čitljivom obliku. */
    public function issue(User $user): string;

    public function revokeCurrent(User $user): void;
}
