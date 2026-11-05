<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServicePolicy
{
    public function create(User $user): bool
    {
        return $user->role->managesServices();
    }

    public function update(User $user, Service $service): bool
    {
        return $user->role->managesServices();
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->role->managesServices();
    }

    /**
     * Dodela usluge radniku: samo radniku (422 za druge uloge), i samo sopstvenom radniku (admin bilo kom).
     */
    public function manageWorker(User $user, Service $service, User $worker): Response
    {
        if (! $user->role->managesServices()) {
            return Response::deny();
        }

        if ($worker->role !== UserRole::Worker) {
            return Response::denyWithStatus(422);
        }

        return $user->role === UserRole::Admin || $worker->client_id === $user->id
            ? Response::allow()
            : Response::deny();
    }
}
