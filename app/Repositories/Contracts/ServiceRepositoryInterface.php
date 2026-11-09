<?php

namespace App\Repositories\Contracts;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface ServiceRepositoryInterface
{
    /**
     * Usluge firme sa radnicima (id, name); opciono samo one koje radi dati radnik.
     *
     * @return Collection<int, Service>
     */
    public function allWithWorkers(?int $workerId = null): Collection;

    public function findActiveForWorker(int $serviceId, int $workerId): ?Service;

    /** Aktivna usluga sa radnicima koji je rade. */
    public function findActiveWithWorkers(int $serviceId): ?Service;

    /**
     * @return BaseCollection<int, int>
     */
    public function activeIds(): BaseCollection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Service;
}
