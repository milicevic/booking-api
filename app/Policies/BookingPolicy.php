<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    /** Rezervaciju potvrđuje ili odbija vlasnik firme kod čijeg radnika je zakazana. */
    public function confirm(User $user, Booking $booking): Response
    {
        return $this->ownsBooking($user, $booking);
    }

    public function reject(User $user, Booking $booking): Response
    {
        return $this->ownsBooking($user, $booking);
    }

    private function ownsBooking(User $user, Booking $booking): Response
    {
        return $booking->slot->worker->client_id === $user->id
            ? Response::allow()
            : Response::deny(__('messages.booking_not_accessible'));
    }
}
