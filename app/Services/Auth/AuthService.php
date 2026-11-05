<?php

namespace App\Services\Auth;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Repositories\Contracts\UserLookupRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\CurrentTenantInterface;
use App\Services\Contracts\TokenIssuerInterface;
use Illuminate\Contracts\Hashing\Hasher;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserLookupRepositoryInterface $users,
        private readonly TokenIssuerInterface $tokens,
        private readonly CurrentTenantInterface $currentTenant,
        private readonly Hasher $hasher,
    ) {}

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmailAcrossTenants($email);

        if (! $user || ! $user->password || ! $this->hasher->check($password, $user->password)) {
            throw DomainException::unauthorized(__('messages.wrong_credentials'));
        }

        match ($user->loginDeniedReason($this->currentTenant->get())) {
            'suspended' => throw DomainException::forbidden(__('messages.account_suspended')),
            'email_not_verified' => throw DomainException::forbidden(__('messages.email_not_verified'), 'email_not_verified'),
            'wrong_tenant' => throw DomainException::unauthorized(__('messages.wrong_credentials')),
            null => null,
        };

        return ['token' => $this->tokens->issue($user), 'user' => $user];
    }

    public function logout(User $user): void
    {
        $this->tokens->revokeCurrent($user);
    }

    public function acceptInvite(string $inviteToken, string $password): array
    {
        $worker = $this->users->findWorkerByInviteToken($inviteToken)
            ?? throw DomainException::notFound(__('messages.invalid_invite_token'));

        $worker->update([
            'password' => $password,
            'invite_token' => null,
        ]);

        // Pozivnica stiže na mejl, pa njeno prihvatanje potvrđuje i email adresu
        $worker->markEmailAsVerified();

        return ['token' => $this->tokens->issue($worker), 'user' => $worker->fresh() ?? $worker];
    }
}
