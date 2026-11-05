<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Slot;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class EloquentBookingRepository implements BookingRepositoryInterface
{
    public function forWorkers(BaseCollection|array $workerIds): Collection
    {
        return Booking::with(['slot.worker', 'service'])
            ->whereHas('slot', fn ($query) => $query->whereIn('worker_id', $workerIds))
            ->latest()
            ->get();
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

    public function activeIntervalsForWorkerOnDate(int $workerId, string $date): array
    {
        return Booking::query()
            ->whereIn('status', BookingStatus::active())
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
