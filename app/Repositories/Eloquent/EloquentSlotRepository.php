<?php

namespace App\Repositories\Eloquent;

use App\Models\Slot;
use App\Repositories\Contracts\SlotRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class EloquentSlotRepository implements SlotRepositoryInterface
{
    public function availableForPublic(array $filters): Collection
    {
        $date = $filters['date'] ?? null;
        $workerId = $filters['worker_id'] ?? null;
        $serviceId = $filters['service_id'] ?? null;

        return Slot::with(['worker.workerProfile', 'worker.services' => fn ($query) => $query->where('is_active', true)])
            ->where('is_available', true)
            ->where(fn ($query) => $query
                ->whereDate('date', '>', today())
                ->orWhere(fn ($today) => $today
                    ->whereDate('date', today())
                    ->where('start_time', '>', now()->format('H:i:s'))))
            ->when($date, fn ($query) => $query->whereDate('date', $date))
            ->when($workerId, fn ($query) => $query->where('worker_id', $workerId))
            ->when($serviceId, fn ($query) => $query->whereHas(
                'worker.services',
                fn ($services) => $services->where('services.id', $serviceId)->where('services.is_active', true)
            ))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function forWorkers(BaseCollection|array $workerIds): Collection
    {
        return Slot::with('worker', 'booking')
            ->whereIn('worker_id', $workerIds)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function upcomingForWorker(int $workerId, ?string $date = null): Collection
    {
        return Slot::where('worker_id', $workerId)
            ->whereDate('date', '>=', today())
            ->when($date, fn ($query) => $query->whereDate('date', $date))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function forWorkerOnDate(int $workerId, string $date): Collection
    {
        return Slot::where('worker_id', $workerId)->whereDate('date', $date)->get();
    }

    public function lockWorkerDay(int $workerId, string $date): void
    {
        Slot::where('worker_id', $workerId)->whereDate('date', $date)->lockForUpdate()->get();
    }

    public function overlapsForWorker(int $workerId, string $date, string $start, string $end): bool
    {
        // Dodirivanje nije preklapanje: 09:00–10:00 i 10:00–11:00 mogu zajedno
        return Slot::where('worker_id', $workerId)
            ->whereDate('date', $date)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }

    public function findOrFail(int $id): Slot
    {
        return Slot::findOrFail($id);
    }

    public function create(array $attributes): Slot
    {
        return Slot::create($attributes);
    }
}
