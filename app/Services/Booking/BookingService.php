<?php

namespace App\Services\Booking;

use App\Enums\BookingLinkAction;
use App\Enums\BookingStatus;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\SlotRepositoryInterface;
use App\Services\Contracts\BookingNotifierInterface;
use App\Services\Contracts\BookingServiceInterface;
use App\Services\Contracts\SlotAvailabilityServiceInterface;
use App\Services\Contracts\VisibleWorkersResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BookingService implements BookingServiceInterface
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly SlotRepositoryInterface $slots,
        private readonly ServiceRepositoryInterface $services,
        private readonly SlotAvailabilityServiceInterface $availability,
        private readonly BookingNotifierInterface $notifier,
        private readonly VisibleWorkersResolverInterface $visibleWorkers,
    ) {}

    public function listFor(User $user, ?int $clientId = null): Collection
    {
        return $this->bookings->forWorkers($this->visibleWorkers->workerIdsFor($user, $clientId));
    }

    public function create(array $data): Booking
    {
        $booking = DB::transaction(function () use ($data): Booking {
            $slot = $this->slots->findOrFail((int) $data['slot_id']);
            $date = $slot->date->toDateString();

            // Zaključavamo sve termine radnika tog dana, da dve istovremene rezervacije ne zauzmu isto vreme
            $this->slots->lockWorkerDay($slot->worker_id, $date);

            $service = $this->resolveService($slot, isset($data['service_id']) ? (int) $data['service_id'] : null);

            $startTime = isset($data['start_time']) ? "{$data['start_time']}:00" : $slot->start_time;
            $time = collect($this->availability->bookableTimes($slot->worker_id, $service?->duration_minutes, $date))
                ->first(fn (array $time): bool => $time['start_time'] === $startTime)
                ?? throw DomainException::conflict(__('messages.slot_unavailable'));

            $booking = $this->bookings->create([
                ...$data,
                'slot_id' => $time['slot_id'],
                'start_time' => $time['start_time'],
                'end_time' => $time['end_time'],
                'status' => BookingStatus::Pending,
                'locale' => app()->getLocale(),
            ]);

            $this->availability->refreshAvailability($slot->worker_id, $date);

            return $booking;
        });

        $booking->load('slot.worker.client.clientProfile');

        if ($booking->slot->worker->client?->clientProfile?->auto_confirm_bookings) {
            $booking->update(['status' => BookingStatus::Confirmed]);
        }

        $this->notifier->created($booking);

        return $booking->load(['slot.worker', 'service']);
    }

    public function findByToken(string $token, array $with = ['slot.worker', 'service']): Booking
    {
        return $this->bookings->findByTokenOrFail($token, $with);
    }

    public function cancel(string $token, bool $notifyCustomer = false): Booking
    {
        $booking = $this->bookings->findActiveByTokenOrFail($token, ['slot']);

        $this->changeStatusAndFreeTime($booking, BookingStatus::Cancelled);

        if ($notifyCustomer) {
            $this->notifier->cancelled($booking);
        }

        return $booking;
    }

    public function confirm(Booking $booking): void
    {
        $this->ensurePending($booking);

        $booking->update(['status' => BookingStatus::Confirmed]);
        $this->notifier->confirmed($booking);
    }

    public function reject(Booking $booking): void
    {
        $this->ensurePending($booking);

        $this->changeStatusAndFreeTime($booking, BookingStatus::Rejected);
        $this->notifier->rejected($booking);
    }

    public function applyLinkAction(string $token, BookingLinkAction $action): Booking
    {
        $booking = $this->bookings->findByTokenOrFail($token, ['slot']);

        match ($action) {
            BookingLinkAction::Confirm => $this->confirm($booking),
            BookingLinkAction::Reject => $this->reject($booking),
        };

        return $booking;
    }

    /**
     * Ako radnik nudi usluge, kupac mora da izabere jednu od njih (aktivnu i dodeljenu radniku).
     */
    private function resolveService(Slot $slot, ?int $serviceId): ?Service
    {
        if ($serviceId === null) {
            if ($this->services->workerHasActiveServices($slot->worker_id)) {
                throw DomainException::unprocessable(__('messages.service_required'));
            }

            return null;
        }

        return $this->services->findActiveForWorker($serviceId, $slot->worker_id)
            ?? throw DomainException::unprocessable(__('messages.service_unavailable_for_worker'));
    }

    private function ensurePending(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw DomainException::unprocessable(__('messages.booking_not_pending'));
        }
    }

    /** Status koji oslobađa vreme radnika, pa se odmah osvežava dostupnost termina tog dana. */
    private function changeStatusAndFreeTime(Booking $booking, BookingStatus $status): void
    {
        DB::transaction(function () use ($booking, $status): void {
            $booking->update(['status' => $status]);
            $this->availability->refreshAvailability($booking->slot->worker_id, $booking->slot->date->toDateString());
        });
    }
}
