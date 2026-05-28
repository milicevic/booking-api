<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejectedCustomer extends Notification
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

        return (new MailMessage)
            ->theme('green')
            ->subject('Rezervacija odbijena')
            ->greeting('Zdravo, ' . $this->booking->customer_name . '!')
            ->line('Nažalost, vaša rezervacija je odbijena.')
            ->panel(implode("\n", [
                '**Radnik:** ' . $worker->name,
                '**Datum:** ' . $slot->date->format('d.m.Y'),
                '**Vreme:** ' . $slot->start_time . ' – ' . $slot->end_time,
            ]))
            ->line('Slobodni termini su i dalje dostupni — možete probati rezervisati drugi.')
            ->salutation('Booking App');
    }
}
