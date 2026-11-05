<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Pristup radnicima (vlasnik firme i admin) i klijentima (samo admin).
 */
class UserPolicy
{
    public function view(User $user, User $worker): bool
    {
        return $this->managesWorker($user, $worker);
    }

    public function update(User $user, User $worker): bool
    {
        return $this->managesWorker($user, $worker);
    }

    public function delete(User $user, User $worker): bool
    {
        return $this->managesWorker($user, $worker);
    }

    public function viewAnyClients(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function suspend(User $user, User $client): Response
    {
        if ($user->role !== UserRole::Admin) {
            return Response::deny();
        }

        return $client->role === UserRole::Client
            ? Response::allow()
            : Response::denyWithStatus(422);
    }

    private function managesWorker(User $user, User $worker): bool
    {
        return $user->role === UserRole::Admin || $worker->client_id === $user->id;
    }
}
