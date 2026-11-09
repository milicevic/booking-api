<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function allWithWorkers(?int $workerId = null): Collection
    {
        return Service::query()
            ->when($workerId, fn ($query) => $query->whereHas('workers', fn ($workers) => $workers->where('users.id', $workerId)))
            ->with(['workers' => fn ($query) => $query->select('users.id', 'users.name')])
            ->get();
    }

    public function findActiveForWorker(int $serviceId, int $workerId): ?Service
    {
        return Service::where('id', $serviceId)
            ->where('is_active', true)
            ->whereHas('workers', fn ($query) => $query->where('users.id', $workerId))
            ->first();
    }

    public function findActiveWithWorkers(int $serviceId): ?Service
    {
        return Service::where('id', $serviceId)
            ->where('is_active', true)
            ->with(['workers' => fn ($query) => $query->select('users.id', 'users.name')])
            ->first();
    }

    public function activeIds(): BaseCollection
    {
        return Service::where('is_active', true)->pluck('id');
    }

    public function create(array $attributes): Service
    {
        return Service::create($attributes);
    }
}
