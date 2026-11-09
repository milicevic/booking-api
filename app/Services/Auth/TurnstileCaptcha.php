<?php

namespace App\Services\Auth;

use App\Services\Contracts\CaptchaVerifierInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile (nevidljiva captcha) na javnim formama: rezervacija i registracija.
 * Bez TURNSTILE_SECRET_KEY je isključena (lokalni razvoj, testovi).
 */
class TurnstileCaptcha implements CaptchaVerifierInterface
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.turnstile.secret'));
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (ConnectionException $e) {
            // Ako Cloudflare nije dostupan, ne blokiramo zakazivanje; ograničenje broja zahteva i dalje važi
            Log::warning('Turnstile verification unavailable', ['error' => $e->getMessage()]);

            return true;
        }

        return $response->json('success') === true;
    }
}
