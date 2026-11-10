<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Contracts\PasswordResetServiceInterface;
use Illuminate\Support\Facades\Password;

/**
 * Zaboravljena lozinka preko Laravel Password brokera (tabela password_reset_tokens, link važi 60 minuta).
 * Mejl šalje User::sendPasswordResetNotification() sa linkom na sajt firme.
 */
class PasswordResetService implements PasswordResetServiceInterface
{
    public function sendLink(string $email): void
    {
        Password::broker()->sendResetLink(['email' => $email]);
    }

    public function reset(array $credentials): string
    {
        return Password::broker()->reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                // Link je stigao na mejl, pa je adresa time potvrđena; nedovršena pozivnica više ne važi
                'email_verified_at' => $user->email_verified_at ?? now(),
                'invite_token' => null,
            ])->save();

            // Odjava sa svih uređaja: stara lozinka je možda bila otkrivena
            $user->tokens()->delete();
        });
    }
}
