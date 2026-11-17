<?php

namespace App\Repositories\Eloquent;

use App\Enums\SubscriptionStatus;
use App\Models\WorkerSchedule;
use App\Repositories\Contracts\WorkerScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentWorkerScheduleRepository implements WorkerScheduleRepositoryInterface
{
    public function findForWorker(int $workerId): ?WorkerSchedule
    {
        return WorkerSchedule::where('worker_id', $workerId)->first();
    }

    public function saveForWorker(int $workerId, array $attributes): WorkerSchedule
    {
        return WorkerSchedule::updateOrCreate(['worker_id' => $workerId], $attributes);
    }

    public function deleteForWorker(int $workerId): void
    {
        WorkerSchedule::where('worker_id', $workerId)->delete();
    }

    public function dueForExtension(string $until): Collection
    {
        // Komanda radi za sve firme odjednom, pa se tenant scope namerno zaobilazi
        return WorkerSchedule::withoutGlobalScopes()
            ->with('worker')
            ->where(fn ($query) => $query->whereNull('generated_until')->orWhereDate('generated_until', '<', $until))
            ->whereHas('tenant', fn ($tenant) => $tenant->whereIn('subscription_status', [
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::Active->value,
            ]))
            ->whereHas('worker', fn ($worker) => $worker->withoutGlobalScopes()->where('is_suspended', false))
            ->get();
    }
}
