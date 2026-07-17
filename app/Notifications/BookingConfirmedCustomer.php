<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BookingConfirmedCustomer extends Notification
{
    public function __construct(
        private Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [WebPushChannel::class];
        if ($notifiable->customer_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        $slot = $this->booking->slot;
        $t = 'notifications.booking_confirmed_customer_push';

        return (new WebPushMessage)
            ->title(__("{$t}.title"))
            ->body(__("{$t}.body", [
                'worker' => $slot->worker->name,
                'date' => $slot->date->format('d.m.Y'),
                'start' => $slot->start_time,
            ]))
            ->icon('/icon.png')
            ->data(['url' => '/manage/'.$this->booking->token]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->booking->slot;
        $worker = $slot->worker;
        $t = 'notifications.booking_confirmed_customer';

        $mail = (new MailMessage)
            ->theme('green')
            ->subject(__("{$t}.subject"))
            ->greeting(__("{$t}.greeting", ['name' => $this->booking->customer_name]))
            ->line(__("{$t}.line"))
            ->line(__("{$t}.worker", ['name' => $worker->name]));

        if ($this->booking->service) {
            $mail->line(__("{$t}.service", ['name' => $this->booking->service->name]));
        }

        return $mail
            ->line(__("{$t}.date", ['date' => $slot->date->format('d.m.Y')]))
            ->line(__("{$t}.time", ['start' => $slot->start_time, 'end' => $slot->end_time]))
            ->action(__("{$t}.action"), route('booking.manage', $this->booking->token))
            ->salutation(__("{$t}.salutation"));
    }
}
