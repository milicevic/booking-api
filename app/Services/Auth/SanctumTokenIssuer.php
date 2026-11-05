<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Contracts\TokenIssuerInterface;

class SanctumTokenIssuer implements TokenIssuerInterface
{
    private const TOKEN_NAME = 'api-token';

    public function issue(User $user): string
    {
        return $user->createToken(self::TOKEN_NAME)->plainTextToken;
    }

    public function revokeCurrent(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
