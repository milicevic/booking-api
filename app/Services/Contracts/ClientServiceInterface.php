<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ClientServiceInterface
{
    /**
     * @return Collection<int, User>
     */
    public function allClients(): Collection;

    public function toggleSuspension(User $client): User;

    /**
     * @param  array{auto_confirm_bookings?: bool, customer_contact?: string}  $settings
     */
    public function updateSettings(User $client, array $settings): User;
}
