<?php

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserLookupRepositoryInterface;
use App\Services\Contracts\GoogleAuthServiceInterface;
use App\Services\Contracts\TenantRegistrationServiceInterface;
use App\Services\Contracts\TokenIssuerInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as GoogleUser;

/**
 * Prijava i registracija preko Google naloga.
 *
 * Google dozvoljava samo unapred upisane callback adrese, pa je callback jedan za sve sajtove firmi.
 * Adresa sa koje je korisnik krenuo putuje kroz šifrovan `state`.
 */
class GoogleAuthService implements GoogleAuthServiceInterface
{
    private const STATE_TTL_MINUTES = 10;

    public function __construct(
        private readonly UserLookupRepositoryInterface $users,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantRegistrationServiceInterface $registration,
        private readonly TokenIssuerInterface $tokens,
        private readonly Encrypter $encrypter,
    ) {}

    public function hostnameOf(string $host): ?string
    {
        if (! preg_match('/^([a-z0-9-]+(?:\.[a-z0-9-]+)*)(?::\d{1,5})?$/i', $host, $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }

    public function isKnownHost(string $hostname): bool
    {
        return $this->isPlatformHost($hostname) || $this->tenants->findByHost($hostname) !== null;
    }

    public function isPlatformHost(string $hostname): bool
    {
        return Tenant::isPlatformHost($hostname);
    }

    public function encodeState(array $payload): string
    {
        return $this->encrypter->encryptString((string) json_encode([
            ...$payload,
            'exp' => now()->addMinutes(self::STATE_TTL_MINUTES)->timestamp,
        ]));
    }

    public function decodeState(mixed $state): ?array
    {
        if (! is_string($state) || $state === '') {
            return null;
        }

        try {
            $payload = json_decode($this->encrypter->decryptString($state), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload) || ! is_string($payload['host'] ?? null) || ! is_int($payload['exp'] ?? null)) {
            return null;
        }

        /** @var array{intent: string, host: string, app_name: ?string, exp: int} $payload */
        return $payload['exp'] >= now()->timestamp ? $payload : null;
    }

    public function authenticate(GoogleUser $googleUser, array $state): array
    {
        $email = $googleUser->getEmail();

        $raw = $googleUser instanceof AbstractUser ? $googleUser->getRaw() : [];

        if (($raw['email_verified'] ?? false) !== true || ! $email) {
            return ['error' => 'email_not_verified'];
        }

        $user = $this->findOrLinkUser($googleUser, $email);

        if (! $user && $state['intent'] === 'register') {
            $user = $this->registration->registerWithGoogle(
                (string) $state['app_name'],
                $googleUser->getName() ?: $email,
                $email,
                (string) $googleUser->getId(),
            );
        }

        if (! $user) {
            return ['error' => 'account_not_found'];
        }

        $hostname = (string) $this->hostnameOf($state['host']);
        $currentTenant = $this->isPlatformHost($hostname) ? null : $this->tenants->findByHost($hostname);
        $deniedReason = $user->loginDeniedReason($currentTenant);

        if ($deniedReason) {
            return ['error' => $deniedReason];
        }

        return ['token' => $this->tokens->issue($user)];
    }

    /**
     * Traži korisnika po Google nalogu, a zatim po emailu i povezuje ga sa Google nalogom.
     * Google je već potvrdio da je adresa korisnikova, pa se i nalog vodi kao potvrđen.
     */
    private function findOrLinkUser(GoogleUser $googleUser, string $email): ?User
    {
        $googleId = (string) $googleUser->getId();
        $user = $this->users->findByGoogleIdAcrossTenants($googleId)
            ?? $this->users->findByEmailAcrossTenants($email);

        if (! $user) {
            return null;
        }

        $user->google_id ??= $googleId;
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
