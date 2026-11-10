<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\Messages\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingPendingCustomer extends Notification
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
        $t = 'notifications.booking_pending_customer';

        return BrandedMailMessage::for($this->booking->tenant ?? $this->booking->slot->worker->tenant)
            ->subject(__("{$t}.subject"))
            ->status(BookingStatus::Pending)
            ->greeting(__("{$t}.greeting", ['name' => $this->booking->customer_name]))
            ->line(__("{$t}.line1"))
            ->bookingDetails($this->booking)
            ->line(__("{$t}.line2"))
            ->action(__("{$t}.action"), $this->booking->manageUrl())
            ->salutation(__("{$t}.salutation"));
    }
}
