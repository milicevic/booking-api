<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\ConfirmationMode;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelledCustomer;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingPendingClient;
use App\Notifications\BookingPendingCustomer;
use App\Notifications\BookingRejectedCustomer;
use App\Notifications\BookingRescheduledCustomer;
use App\Services\Contracts\BookingNotifierInterface;

class BookingNotifier implements BookingNotifierInterface
{
    public function created(Booking $booking): void
    {
        if ($booking->status === BookingStatus::Confirmed) {
            $booking->notify(new BookingConfirmedCustomer($booking));
            $booking->slot->worker->client?->notify(new BookingConfirmedClient($booking));

            return;
        }

        $booking->notify(new BookingPendingCustomer($booking));
        $this->approver($booking)?->notify(new BookingPendingClient($booking));
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

    public function rescheduled(Booking $booking): void
    {
        $booking->notify(new BookingRescheduledCustomer($booking));
    }

    public function rescheduledByCustomer(Booking $booking): void
    {
        if ($booking->status === BookingStatus::Confirmed) {
            $booking->notify(new BookingRescheduledCustomer($booking));
            $booking->slot->worker->client?->notify(new BookingConfirmedClient($booking, rescheduled: true));

            return;
        }

        $booking->notify(new BookingPendingCustomer($booking));
        $this->approver($booking)?->notify(new BookingPendingClient($booking, rescheduled: true));
    }

    /**
     * Ko dobija zahtev za potvrdu: radnik u režimu "worker", inače vlasnik.
     * Radnik bez emaila (npr. vlasnik upisan kao radnik) nema nalog, pa zahtev ide vlasniku.
     */
    private function approver(Booking $booking): ?User
    {
        $worker = $booking->slot->worker;

        if ($booking->confirmationMode() === ConfirmationMode::Worker && $worker->email) {
            return $worker;
        }

        return $worker->client;
    }
}
