<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\GoogleAuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Google OAuth: kontroler samo preusmerava; pravila prijave i registracije su u GoogleAuthService.
 * Token se vraća frontendu u URL fragmentu.
 */
class GoogleAuthController extends Controller
{
    public function __construct(private readonly GoogleAuthServiceInterface $google) {}

    public function redirect(Request $request): SymfonyRedirectResponse
    {
        $host = $request->query('host');
        $hostname = is_string($host) ? $this->google->hostnameOf($host) : null;

        if (! is_string($host) || ! $hostname || ! $this->google->isKnownHost($hostname)) {
            return $this->frontendRedirect(null, ['error' => 'invalid_host']);
        }

        $validator = Validator::make($request->query(), [
            'intent' => ['required', 'in:login,register'],
            'app_name' => ['required_if:intent,register', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->frontendRedirect($host, ['error' => 'google_failed']);
        }

        $intent = (string) $request->query('intent');

        // Nova firma se registruje samo na glavnom domenu, nikad sa sajta postojeće firme
        if ($intent === 'register' && ! $this->google->isPlatformHost($hostname)) {
            return $this->frontendRedirect($host, ['error' => 'invalid_host']);
        }

        $appName = $request->query('app_name');
        $state = $this->google->encodeState([
            'intent' => $intent,
            'host' => $host,
            'app_name' => is_string($appName) ? $appName : null,
        ]);

        return $this->driver(['state' => $state])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $this->google->decodeState($request->query('state'));

        if (! $state) {
            return $this->frontendRedirect(null, ['error' => 'google_failed']);
        }

        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $this->driver()->user();
        } catch (Throwable) {
            return $this->frontendRedirect($state['host'], ['error' => 'google_failed']);
        }

        return $this->frontendRedirect($state['host'], $this->google->authenticate($googleUser, $state));
    }

    /**
     * Google drajver bez sesije (stateless), jer se state prenosi šifrovan kroz URL.
     *
     * @param  array<string, string>  $parameters
     */
    private function driver(array $parameters = []): Provider
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');
        $driver = $driver->stateless();

        return $parameters ? $driver->with($parameters) : $driver;
    }

    /**
     * Vraća korisnika na frontend, na adresu sa koje je krenuo (ili na glavni FRONTEND_URL ako adresa nije poznata).
     *
     * @param  array<string, string>  $fragment
     */
    private function frontendRedirect(?string $host, array $fragment): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $scheme = parse_url($frontendUrl, PHP_URL_SCHEME) ?: 'https';
        $baseUrl = $host ? "{$scheme}://{$host}" : $frontendUrl;

        return redirect()->away("{$baseUrl}/auth/callback#".http_build_query($fragment));
    }
}
