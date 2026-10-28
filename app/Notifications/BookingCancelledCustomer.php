<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledCustomer extends Notification
{
    public function __construct(
        private Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->booking->slot;
        $worker = $slot->worker;
        $t = 'notifications.booking_cancelled_customer';

        return (new MailMessage)
            ->theme('green')
            ->subject(__("{$t}.subject"))
            ->greeting(__("{$t}.greeting", ['name' => $this->booking->customer_name]))
            ->line(__("{$t}.line1"))
            ->line(__("{$t}.worker", ['name' => $worker->name]))
            ->line(__("{$t}.date", ['date' => $slot->date->format('d.m.Y')]))
            ->line(__("{$t}.time", ['start' => substr($this->booking->start_time, 0, 5), 'end' => substr($this->booking->end_time, 0, 5)]))
            ->line(__("{$t}.line2"))
            ->salutation(__("{$t}.salutation"));
    }
}
