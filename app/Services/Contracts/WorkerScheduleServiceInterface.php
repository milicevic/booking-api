<?php

namespace App\Services\Contracts;

use App\Models\User;
use App\Models\WorkerSchedule;

/**
 * Stalni raspored radnika i termini koji se iz njega prave WorkerSchedule::WEEKS_AHEAD nedelja unapred.
 */
interface WorkerScheduleServiceInterface
{
    public function find(User $worker): ?WorkerSchedule;

    /**
     * Čuva raspored i odmah pravi termine za ceo prozor. Budući slobodni termini starog rasporeda
     * (bez rezervacije, neblokirani) se brišu i prave iznova; ručni termini ostaju.
     *
     * @param  array{weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>}  $data
     * @return array{schedule: WorkerSchedule, created: int, skipped: int, removed: int}
     */
    public function save(User $worker, array $data): array;

    /** Raspored prestaje da pravi nove termine; postojeći termini ostaju. */
    public function delete(User $worker): void;

    /**
     * Dopunjuje termine svih rasporeda do kraja prozora (dnevna komanda). Pravi samo dane posle
     * generated_until, pa termini koje je vlasnik obrisao ne niču ponovo.
     *
     * @return array{schedules: int, created: int}
     */
    public function extendAll(): array;
}
