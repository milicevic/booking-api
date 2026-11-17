<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface ClientServiceInterface
{
    /**
     * @return Collection<int, User>
     */
    public function allClients(): Collection;

    public function toggleSuspension(User $client): User;

    /**
     * @param  array{auto_confirm_bookings?: bool, customer_contact?: string, confirmation_mode?: string, reschedule_cutoff_hours?: int, onboarding_completed_at?: ?Carbon}  $settings
     */
    public function updateSettings(User $client, array $settings): User;
}
