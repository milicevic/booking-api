<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Podešavanja sajta firme menja samo njen vlasnik (client).
 */
class TenantPolicy
{
    public function updateBranding(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    public function requestDeploy(User $user): bool
    {
        return $user->role === UserRole::Client;
    }
}
