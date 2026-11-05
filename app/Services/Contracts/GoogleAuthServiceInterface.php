<?php

namespace App\Services\Contracts;

use Laravel\Socialite\Contracts\User as GoogleUser;

interface GoogleAuthServiceInterface
{
    /** Ime hosta bez porta, ili null ako adresa nije ispravna. */
    public function hostnameOf(string $host): ?string;

    /** Da li je adresa glavni domen platforme ili sajt postojeće firme. */
    public function isKnownHost(string $hostname): bool;

    public function isPlatformHost(string $hostname): bool;

    /**
     * @param  array{intent: string, host: string, app_name: ?string}  $payload
     */
    public function encodeState(array $payload): string;

    /**
     * @return array{intent: string, host: string, app_name: ?string, exp: int}|null
     */
    public function decodeState(mixed $state): ?array;

    /**
     * Prijavljuje (ili registruje) korisnika Google nalogom; vraća fragment za frontend: ['token' => ...] ili ['error' => ...].
     *
     * @param  array{intent: string, host: string, app_name: ?string, exp: int}  $state
     * @return array<string, string>
     */
    public function authenticate(GoogleUser $googleUser, array $state): array;
}
