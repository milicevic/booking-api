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
        $manageUrl = route('booking.manage', $this->booking->token);

        return (new MailMessage)
            ->theme('green')
            ->subject('Rezervacija potvrđena ✓')
            ->greeting('Zdravo, ' . $this->booking->customer_name . '!')
            ->line('Vaša rezervacija je **potvrđena**. Vidimo se!')
            ->panel(implode("\n", [
                '**Radnik:** ' . $worker->name,
                '**Datum:** ' . $slot->date->format('d.m.Y'),
                '**Vreme:** ' . $slot->start_time . ' – ' . $slot->end_time,
            ]))
            ->action('Upravljaj rezervacijom', $manageUrl)
            ->salutation('Hvala što koristite naš servis!');
    }
}
