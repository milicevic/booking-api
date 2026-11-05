<?php

namespace App\Services\Client;

use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\Contracts\ClientServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class ClientService implements ClientServiceInterface
{
    public function __construct(private readonly ClientRepositoryInterface $clients) {}

    public function allClients(): Collection
    {
        return $this->clients->allWithProfileAndWorkerCount();
    }

    public function toggleSuspension(User $client): User
    {
        $client->update(['is_suspended' => ! $client->is_suspended]);

        return $client->fresh() ?? $client;
    }

    public function updateSettings(User $client, array $settings): User
    {
        $client->clientProfile()->updateOrCreate(['user_id' => $client->id], $settings);

        return ($client->fresh() ?? $client)->load('clientProfile');
    }
}
