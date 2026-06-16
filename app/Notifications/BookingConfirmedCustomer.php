<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedCustomer extends Notification
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
        $t = 'notifications.booking_confirmed_customer';

        return (new MailMessage)
            ->theme('green')
            ->subject(__("{$t}.subject"))
            ->greeting(__("{$t}.greeting", ['name' => $this->booking->customer_name]))
            ->line(__("{$t}.line"))
            ->line(__("{$t}.worker", ['name' => $worker->name]))
            ->line(__("{$t}.date", ['date' => $slot->date->format('d.m.Y')]))
            ->line(__("{$t}.time", ['start' => $slot->start_time, 'end' => $slot->end_time]))
            ->action(__("{$t}.action"), route('booking.manage', $this->booking->token))
            ->salutation(__("{$t}.salutation"));
    }
}
