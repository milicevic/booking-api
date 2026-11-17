<?php

namespace App\Policies;

use App\Enums\ConfirmationMode;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    /**
     * Rezervaciju potvrđuje ili odbija vlasnik firme; u režimu "worker" i radnik kod koga je zakazana.
     */
    public function confirm(User $user, Booking $booking): Response
    {
        return $this->managesBooking($user, $booking);
    }

    public function reject(User $user, Booking $booking): Response
    {
        return $this->managesBooking($user, $booking);
    }

    /**
     * Pomeranje: vlasnik na termin bilo kog svog radnika, radnik (režim "worker") samo na svoj termin.
     */
    public function reschedule(User $user, Booking $booking, Slot $target): Response
    {
        if ($this->managesBooking($user, $booking)->denied()) {
            return $this->managesBooking($user, $booking);
        }

        $allowed = $this->isOwner($user, $booking)
            ? $target->worker->client_id === $user->id
            : $target->worker_id === $user->id;

        return $allowed ? Response::allow() : Response::deny(__('messages.slot_not_accessible'));
    }

    private function managesBooking(User $user, Booking $booking): Response
    {
        $isAssignedWorker = $user->role === UserRole::Worker
            && $booking->slot->worker_id === $user->id
            && $booking->confirmationMode() === ConfirmationMode::Worker;

        return $this->isOwner($user, $booking) || $isAssignedWorker
            ? Response::allow()
            : Response::deny(__('messages.booking_not_accessible'));
    }

    private function isOwner(User $user, Booking $booking): bool
    {
        return $booking->slot->worker->client_id === $user->id;
    }
}
