<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** Vlasnici firmi (korisnici sa ulogom client). */
interface ClientRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function allWithProfileAndWorkerCount(): Collection;

    /**
     * @param  array{name: string, email: string, password: ?string, tenant_id: int}  $attributes
     */
    public function create(array $attributes): User;
}
