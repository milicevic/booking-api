<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Repositories\Contracts\WorkerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

class EloquentWorkerRepository implements WorkerRepositoryInterface
{
    public function workersOfClient(int $clientId): Collection
    {
        return User::where('client_id', $clientId)
            ->where('role', UserRole::Worker)
            ->with('workerProfile')
            ->get();
    }

    public function workerIdsOfClient(int $clientId): BaseCollection
    {
        return User::where('client_id', $clientId)
            ->where('role', UserRole::Worker)
            ->pluck('id');
    }

    public function workerIdsOfTenant(int $tenantId): BaseCollection
    {
        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('role', UserRole::Worker)
            ->pluck('id');
    }

    public function createWithProfile(array $attributes, ?string $phone): User
    {
        return DB::transaction(function () use ($attributes, $phone): User {
            $worker = User::create([...$attributes, 'role' => UserRole::Worker]);

            WorkerProfile::create([
                'user_id' => $worker->id,
                'phone' => $phone,
            ]);

            return $worker;
        });
    }
}
