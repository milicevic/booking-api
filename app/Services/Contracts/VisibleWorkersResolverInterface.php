<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Radnici čije termine i rezervacije korisnik sme da vidi.
 */
interface VisibleWorkersResolverInterface
{
    /**
     * @return Collection<int, int>
     */
    public function workerIdsFor(User $user, ?int $clientId = null): Collection;
}
