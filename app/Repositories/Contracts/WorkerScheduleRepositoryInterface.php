<?php

namespace App\Repositories\Contracts;

use App\Models\WorkerSchedule;
use Illuminate\Database\Eloquent\Collection;

interface WorkerScheduleRepositoryInterface
{
    public function findForWorker(int $workerId): ?WorkerSchedule;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveForWorker(int $workerId, array $attributes): WorkerSchedule;

    public function deleteForWorker(int $workerId): void;

    /**
     * Rasporedi kojima termini ne pokrivaju prozor do $until, u firmama čija pretplata važi,
     * za radnike koji nisu suspendovani. Bez obzira na tenant (pokreće ih komanda).
     *
     * @return Collection<int, WorkerSchedule>
     */
    public function dueForExtension(string $until): Collection;
}
