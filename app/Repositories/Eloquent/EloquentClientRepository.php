<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentClientRepository implements ClientRepositoryInterface
{
    public function allWithProfileAndWorkerCount(): Collection
    {
        return User::where('role', UserRole::Client)
            ->with('clientProfile')
            ->withCount('workers')
            ->get();
    }

    public function create(array $attributes): User
    {
        /** @var User $client */
        $client = User::withoutGlobalScopes()->create([...$attributes, 'role' => UserRole::Client]);

        return $client;
    }
}
