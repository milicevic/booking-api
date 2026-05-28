<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedClient extends Notification
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
        $contact = $this->booking->customer_phone ?? $this->booking->customer_email ?? '—';

        return (new MailMessage)
            ->theme('green')
            ->subject('Nova potvrđena rezervacija — ' . $this->booking->customer_name)
            ->greeting('Nova rezervacija potvrđena!')
            ->line('Automatski je potvrđena nova rezervacija.')
            ->panel(implode("\n", array_filter([
                '**Korisnik:** ' . $this->booking->customer_name,
                '**Kontakt:** ' . $contact,
                '**Radnik:** ' . $worker->name,
                '**Datum:** ' . $slot->date->format('d.m.Y'),
                '**Vreme:** ' . $slot->start_time . ' – ' . $slot->end_time,
                $this->booking->note ? '**Napomena:** ' . $this->booking->note : null,
            ])))
            ->action('Otvori dashboard', config('app.frontend_url') . '/admin')
            ->salutation('Booking App');
    }
}
