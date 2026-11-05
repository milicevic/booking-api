<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use App\Notifications\WelcomeClient;
use App\Repositories\Contracts\UserLookupRepositoryInterface;
use App\Services\Contracts\EmailVerificationServiceInterface;

/**
 * Potvrda email adrese za naloge koji se prijavljuju lozinkom.
 */
class EmailVerificationService implements EmailVerificationServiceInterface
{
    public function __construct(private readonly UserLookupRepositoryInterface $users) {}

    public function verify(int $userId, string $hash, bool $hasValidSignature): string
    {
        $user = $this->users->findAcrossTenants($userId);

        $isValidLink = $user
            && $hasValidSignature
            && hash_equals(sha1($user->getEmailForVerification()), $hash);

        if (! $isValidLink) {
            return $this->loginUrl($user, 'invalid');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            if ($user->tenant) {
                $user->notify(new WelcomeClient($user->tenant));
            }
        }

        return $this->loginUrl($user, '1');
    }

    public function resend(string $email): void
    {
        $user = $this->users->findByEmailAcrossTenants($email);

        if ($user && ! $user->hasVerifiedEmail() && $user->tenant) {
            $user->notify(new VerifyEmailAddress($user->tenant));
        }
    }

    private function loginUrl(?User $user, string $status): string
    {
        $path = "/admin/login?verified={$status}";

        return $user?->tenant
            ? $user->tenant->siteUrl($path)
            : rtrim((string) config('app.frontend_url'), '/').$path;
    }
}
