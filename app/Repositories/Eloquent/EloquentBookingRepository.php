<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Slot;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class EloquentBookingRepository implements BookingRepositoryInterface
{
    public function forWorkers(BaseCollection|array $workerIds, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Booking::with(['slot.worker', 'service'])
            ->whereHas('slot', fn ($slot) => $slot
                ->whereIn('worker_id', $workerIds)
                ->when($filters['worker_id'] ?? null, fn ($slot, $workerId) => $slot->where('worker_id', $workerId))
                ->when($filters['from'] ?? null, fn ($slot, $from) => $slot->whereDate('date', '>=', $from))
                ->when($filters['to'] ?? null, fn ($slot, $to) => $slot->whereDate('date', '<=', $to)))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest();

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    public function findByTokenOrFail(string $token, array $with = []): Booking
    {
        return Booking::with($with)->where('token', $token)->firstOrFail();
    }

    public function findActiveByTokenOrFail(string $token, array $with = []): Booking
    {
        return Booking::with($with)
            ->where('token', $token)
            ->whereIn('status', BookingStatus::active())
            ->firstOrFail();
    }

    public function activeIntervalsForWorkerOnDate(int $workerId, string $date, ?int $exceptBookingId = null): array
    {
        return Booking::query()
            ->whereIn('status', BookingStatus::active())
            ->when($exceptBookingId, fn ($query) => $query->whereKeyNot($exceptBookingId))
            ->whereHas('slot', fn ($query) => $query->where('worker_id', $workerId)->whereDate('date', $date))
            ->get(['start_time', 'end_time'])
            ->map(fn (Booking $booking): array => [Slot::toMinutes($booking->start_time), Slot::toMinutes($booking->end_time)])
            ->sortBy(0)
            ->values()
            ->all();
    }

    public function create(array $attributes): Booking
    {
        return Booking::create($attributes);
    }
}
