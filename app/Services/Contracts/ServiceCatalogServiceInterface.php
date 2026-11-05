<?php

namespace App\Services\Contracts;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ServiceCatalogServiceInterface
{
    /**
     * @return Collection<int, Service>
     */
    public function list(?int $workerId = null): Collection;

    /**
     * Nova usluga; podrazumevano je rade svi radnici firme.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Service;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Service $service, array $attributes): Service;

    public function delete(Service $service): void;

    public function assignWorker(Service $service, User $worker): void;

    public function unassignWorker(Service $service, User $worker): void;
}
