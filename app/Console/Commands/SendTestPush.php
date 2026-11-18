<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestPushNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use NotificationChannels\WebPush\Events\NotificationFailed;
use NotificationChannels\WebPush\Events\NotificationSent;

/**
 * Šalje probno push obaveštenje na sve uređaje korisnika, da se na serveru proveri podešavanje.
 * Ispisuje šta nedostaje (VAPID ključevi, pretplate) i zašto je slanje odbijeno.
 */
class SendTestPush extends Command
{
    protected $signature = 'push:test {email : Email korisnika (vlasnik, radnik ili admin) koji je uključio obaveštenja}';

    protected $description = 'Send a test web push notification to every device of a user';

    public function handle(): int
    {
        $missing = array_filter([
            'VAPID_PUBLIC_KEY' => config('webpush.vapid.public_key'),
            'VAPID_PRIVATE_KEY' => config('webpush.vapid.private_key'),
            'VAPID_SUBJECT' => config('webpush.vapid.subject'),
        ], fn (mixed $value): bool => ! is_string($value) || $value === '');

        if ($missing !== []) {
            $this->error('Not set: '.implode(', ', array_keys($missing)).'. Generate keys with "php artisan webpush:vapid --show", set them and redeploy.');

            return self::FAILURE;
        }

        // Komanda radi bez tenanta, pa se korisnik traži u svim firmama
        $user = User::withoutGlobalScopes()->where('email', (string) $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $devices = $user->pushSubscriptions()->count();
        $this->line("Devices with notifications enabled: {$devices}");

        if ($devices === 0) {
            $this->warn('The user has not enabled notifications on any device (admin → "Uključi obaveštenja").');

            return self::FAILURE;
        }

        $sent = 0;
        $failures = [];
        Event::listen(NotificationSent::class, function () use (&$sent): void {
            $sent++;
        });
        Event::listen(NotificationFailed::class, function (NotificationFailed $event) use (&$failures): void {
            $failures[] = $event->report->getReason();
        });

        $user->notify(new TestPushNotification);

        $this->info("Delivered to the push service: {$sent} of {$devices}.");

        foreach ($failures as $reason) {
            $this->error("Rejected: {$reason}");
        }

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }
}
