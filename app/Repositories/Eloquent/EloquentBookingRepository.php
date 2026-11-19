<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Slot;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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

    public function countActiveUpcomingForCustomer(?string $phone, ?string $email): int
    {
        return $this->activeUpcomingQuery($phone, $email)->count();
    }

    public function countCreatedSinceForCustomer(?string $phone, ?string $email, \DateTimeInterface $since): int
    {
        return $this->customerQuery($phone, $email)->where('created_at', '>=', $since)->count();
    }

    public function activeUpcomingForCustomer(?string $phone, ?string $email): Collection
    {
        return $this->activeUpcomingQuery($phone, $email)->with(['slot.worker', 'service'])->get();
    }

    public function pendingAcrossTenants(): Collection
    {
        // Komanda radi za sve firme odjednom, pa se tenant scope namerno zaobilazi
        return Booking::withoutGlobalScopes()
            ->with(['slot.worker.client.clientProfile', 'service', 'tenant'])
            ->where('status', BookingStatus::Pending)
            ->get();
    }

    /**
     * Isti kupac = isti telefon ili isti email (email bez obzira na velika slova).
     *
     * @return Builder<Booking>
     */
    private function customerQuery(?string $phone, ?string $email): Builder
    {
        return Booking::query()->where(fn ($query) => $query
            ->when($phone, fn ($q) => $q->orWhere('customer_phone', $phone))
            ->when($email, fn ($q) => $q->orWhereRaw('lower(customer_email) = ?', [mb_strtolower((string) $email)]))
            ->when(! $phone && ! $email, fn ($q) => $q->whereRaw('1 = 0')));
    }

    /**
     * @return Builder<Booking>
     */
    private function activeUpcomingQuery(?string $phone, ?string $email): Builder
    {
        // Još nije počela: termin posle danas, ili danas sa početkom posle sadašnjeg trenutka
        return $this->customerQuery($phone, $email)
            ->whereIn('status', BookingStatus::active())
            ->where(fn ($query) => $query
                ->whereHas('slot', fn ($slot) => $slot->whereDate('date', '>', today()))
                ->orWhere(fn ($today) => $today
                    ->where('start_time', '>', now()->format('H:i:s'))
                    ->whereHas('slot', fn ($slot) => $slot->whereDate('date', today()))));
    }
}
