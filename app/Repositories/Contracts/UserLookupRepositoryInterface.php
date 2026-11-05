<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Nalaženje naloga za prijavu, nezavisno od firme (bez tenant scope-a).
 */
interface UserLookupRepositoryInterface
{
    public function findAcrossTenants(int $id): ?User;

    public function findByEmailAcrossTenants(string $email): ?User;

    public function findByGoogleIdAcrossTenants(string $googleId): ?User;

    public function findWorkerByInviteToken(string $inviteToken): ?User;
}
