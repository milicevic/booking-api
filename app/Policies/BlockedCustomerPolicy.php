<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BlockedCustomer;
use App\Models\User;

/**
 * Listu blokiranih kupaca vidi i menja samo vlasnik firme.
 */
class BlockedCustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    public function delete(User $user, BlockedCustomer $blocked): bool
    {
        return $user->role === UserRole::Client && $blocked->tenant_id === $user->tenant_id;
    }
}
