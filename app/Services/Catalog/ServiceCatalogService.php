<?php

namespace App\Services\Catalog;

use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\WorkerRepositoryInterface;
use App\Services\Contracts\ServiceCatalogServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class ServiceCatalogService implements ServiceCatalogServiceInterface
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly WorkerRepositoryInterface $workers,
    ) {}

    public function list(?int $workerId = null): Collection
    {
        return $this->services->allWithWorkers($workerId);
    }

    public function create(array $attributes): Service
    {
        $service = $this->services->create($attributes);

        // Podrazumevano uslugu rade svi radnici; vlasnik posle može da isključi pojedine
        $service->workers()->sync($this->workers->workerIdsOfTenant((int) $service->tenant_id));

        return $service->load(['workers' => fn ($query) => $query->select('users.id', 'users.name')]);
    }

    public function update(Service $service, array $attributes): Service
    {
        $service->update($attributes);

        return $service->fresh() ?? $service;
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }

    public function assignWorker(Service $service, User $worker): void
    {
        $service->workers()->syncWithoutDetaching([$worker->id]);
    }

    public function unassignWorker(Service $service, User $worker): void
    {
        $service->workers()->detach($worker->id);
    }
}
