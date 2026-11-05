<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingCancelledCustomer;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingPendingClient;
use App\Notifications\BookingPendingCustomer;
use App\Notifications\BookingRejectedCustomer;
use App\Services\Contracts\BookingNotifierInterface;

class BookingNotifier implements BookingNotifierInterface
{
    public function created(Booking $booking): void
    {
        $client = $booking->slot->worker->client;

        if ($booking->status === BookingStatus::Confirmed) {
            $booking->notify(new BookingConfirmedCustomer($booking));
            $client?->notify(new BookingConfirmedClient($booking));

            return;
        }

        $booking->notify(new BookingPendingCustomer($booking));
        $client?->notify(new BookingPendingClient($booking));
    }

    public function confirmed(Booking $booking): void
    {
        $booking->notify(new BookingConfirmedCustomer($booking));
    }

    public function rejected(Booking $booking): void
    {
        $booking->notify(new BookingRejectedCustomer($booking));
    }

    public function cancelled(Booking $booking): void
    {
        if ($booking->customer_email) {
            $booking->notify(new BookingCancelledCustomer($booking));
        }
    }
}
