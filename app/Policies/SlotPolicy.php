<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Slot;
use App\Models\User;

/**
 * Termine uređuje vlasnik firme (za svoje radnike); radnik samo svoje i samo uz dozvolu can_edit_slots.
 */
class SlotPolicy
{
    /** Admin pravi termine bilo kom radniku, vlasnik firme svojim radnicima, radnik sebi (uz can_edit_slots). */
    public function create(User $user, User $worker): bool
    {
        return $user->role === UserRole::Admin
            || $worker->client_id === $user->id
            || $this->editsOwnSlots($user, $worker->id);
    }

    public function update(User $user, Slot $slot): bool
    {
        return $this->manages($user, $slot);
    }

    public function delete(User $user, Slot $slot): bool
    {
        return $this->manages($user, $slot);
    }

    private function manages(User $user, Slot $slot): bool
    {
        return $slot->worker->client_id === $user->id || $this->editsOwnSlots($user, $slot->worker_id);
    }

    private function editsOwnSlots(User $user, int $workerId): bool
    {
        return $user->role === UserRole::Worker && $user->can_edit_slots && $user->id === $workerId;
    }
}
