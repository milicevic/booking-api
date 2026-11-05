<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Slot;
use App\Models\User;

class SlotPolicy
{
    /** Admin pravi termine bilo kom radniku, vlasnik firme samo svojim radnicima. */
    public function create(User $user, User $worker): bool
    {
        return $user->role === UserRole::Admin || $worker->client_id === $user->id;
    }

    public function delete(User $user, Slot $slot): bool
    {
        return $slot->worker->client_id === $user->id;
    }
}
