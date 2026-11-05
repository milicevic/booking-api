<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface WorkerRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function workersOfClient(int $clientId): Collection;

    /**
     * @return BaseCollection<int, int>
     */
    public function workerIdsOfClient(int $clientId): BaseCollection;

    /**
     * @return BaseCollection<int, int>
     */
    public function workerIdsOfTenant(int $tenantId): BaseCollection;

    /**
     * Kreira radnika zajedno sa profilom.
     *
     * @param  array{name: string, email: ?string, client_id: int, invite_token: ?string}  $attributes
     */
    public function createWithProfile(array $attributes, ?string $phone): User;
}
