<?php

use App\Console\Commands\ExpirePendingBookings;
use App\Console\Commands\ExpireTrialSubscriptions;
use App\Console\Commands\ExtendWorkerSchedules;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireTrialSubscriptions::class)->daily();

// Raspored radnika: termini uvek pokrivaju narednih WorkerSchedule::WEEKS_AHEAD nedelja
Schedule::command(ExtendWorkerSchedules::class)->dailyAt('01:00');

// Brisanje isteklih Sanctum tokena (rok važenja: sanctum.expiration)
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Rezervacije koje čekaju potvrdu predugo (ili im je termin počeo) se odbijaju i oslobađaju termin
Schedule::command(ExpirePendingBookings::class)->everyFifteenMinutes();
