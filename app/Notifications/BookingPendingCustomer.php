<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingPendingCustomer extends Notification
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
            ->subject('Zahtjev za rezervaciju primljen ⏳')
            ->greeting('Zdravo, ' . $this->booking->customer_name . '!')
            ->line('Vaš zahtjev za rezervaciju je uspješno primljen i čeka potvrdu.')
            ->panel(implode("\n", [
                '**Radnik:** ' . $worker->name,
                '**Datum:** ' . $slot->date->format('d.m.Y'),
                '**Vreme:** ' . $slot->start_time . ' – ' . $slot->end_time,
            ]))
            ->line('Bićete obaviješteni čim klijent potvrdi ili odbije vašu rezervaciju.')
            ->action('Pogledaj rezervaciju', $manageUrl)
            ->salutation('Hvala što koristite naš servis!');
    }
}
