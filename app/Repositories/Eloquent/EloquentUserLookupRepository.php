<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserLookupRepositoryInterface;

class EloquentUserLookupRepository implements UserLookupRepositoryInterface
{
    public function findAcrossTenants(int $id): ?User
    {
        return User::withoutGlobalScopes()->find($id);
    }

    public function findByEmailAcrossTenants(string $email): ?User
    {
        return User::withoutGlobalScopes()->where('email', $email)->first();
    }

    public function findByGoogleIdAcrossTenants(string $googleId): ?User
    {
        return User::withoutGlobalScopes()->where('google_id', $googleId)->first();
    }

    public function findWorkerByInviteToken(string $inviteToken): ?User
    {
        return User::withoutGlobalScopes()
            ->where('invite_token', $inviteToken)
            ->where('role', UserRole::Worker)
            ->first();
    }
}
