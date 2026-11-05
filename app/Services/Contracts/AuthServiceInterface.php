<?php

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * @return array{token: string, user: User}
     */
    public function login(string $email, string $password): array;

    public function logout(User $user): void;

    /**
     * Radnik prihvata pozivnicu i postavlja lozinku.
     *
     * @return array{token: string, user: User}
     */
    public function acceptInvite(string $inviteToken, string $password): array;
}
