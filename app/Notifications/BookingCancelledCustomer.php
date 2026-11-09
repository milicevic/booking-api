<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\Messages\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledCustomer extends Notification
{
    public function __construct(
        private Booking $booking
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = 'notifications.booking_cancelled_customer';

        return BrandedMailMessage::for($this->booking->tenant ?? $this->booking->slot->worker->tenant)
            ->subject(__("{$t}.subject"))
            ->status(BookingStatus::Cancelled)
            ->greeting(__("{$t}.greeting", ['name' => $this->booking->customer_name]))
            ->line(__("{$t}.line1"))
            ->bookingDetails($this->booking)
            ->line(__("{$t}.line2"));
    }
}
