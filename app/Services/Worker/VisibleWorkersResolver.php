<?php

namespace App\Services\Worker;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\WorkerRepositoryInterface;
use App\Services\Contracts\VisibleWorkersResolverInterface;
use Illuminate\Support\Collection;

class VisibleWorkersResolver implements VisibleWorkersResolverInterface
{
    public function __construct(private readonly WorkerRepositoryInterface $workers) {}

    public function workerIdsFor(User $user, ?int $clientId = null): Collection
    {
        return match (true) {
            $user->role === UserRole::Admin && $clientId !== null => $this->workers->workerIdsOfClient($clientId),
            $user->role === UserRole::Worker => collect([$user->id]),
            default => $this->workers->workerIdsOfClient($user->id),
        };
    }
}
