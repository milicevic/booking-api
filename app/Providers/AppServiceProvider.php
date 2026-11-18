<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;
use NotificationChannels\WebPush\Events\NotificationFailed;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // API vraća resurse bez "data" omotača, kao i pre uvođenja API Resource klasa
        JsonResource::withoutWrapping();

        $this->configureRateLimiting();

        // Odbijen push (pogrešni VAPID ključevi, istekla pretplata…) se inače ne vidi nigde
        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            Log::warning('Web push failed: '.$event->report->getReason(), [
                'push_service' => parse_url($event->subscription->endpoint, PHP_URL_HOST),
                'expired' => $event->report->isSubscriptionExpired(),
            ]);
        });

        // Lozinke: bar 10 znakova, velika i mala slova i broj; na produkciji i provera da nije procurela
        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(10)->letters()->mixedCase()->numbers());
    }

    /**
     * Javne rute koje se mogu zloupotrebiti: pogađanje lozinki, masovni lažni nalozi i rezervacije.
     * Prekoračenje vraća 429 sa prevedenom porukom (lang/{en,sr}/http.php).
     */
    private function configureRateLimiting(): void
    {
        // Po nalogu (email + IP) protiv pogađanja lozinke, i po IP adresi protiv probanja mnogo naloga
        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('bookings', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('google', fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
    }
}
