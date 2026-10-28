<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BookingPendingClient extends Notification
{
    public function __construct(
        private Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $slot = $this->booking->slot;

        return [
            'type' => 'booking_pending',
            'booking_token' => $this->booking->token,
            'customer_name' => $this->booking->customer_name,
            'worker_name' => $slot->worker->name,
            'service_name' => $this->booking->service?->name,
            'date' => $slot->date->format('Y-m-d'),
            'start_time' => $this->booking->start_time,
        ];
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        $slot = $this->booking->slot;
        $worker = $slot->worker;
        $t = 'notifications.booking_pending_client_push';

        return (new WebPushMessage)
            ->title(__("{$t}.title", ['name' => $this->booking->customer_name]))
            ->body(__("{$t}.body", [
                'name' => $this->booking->customer_name,
                'date' => $slot->date->format('d.m.Y'),
                'start' => substr($this->booking->start_time, 0, 5),
                'worker' => $worker->name,
            ]))
            ->icon('/icon.png');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->booking->slot;
        $worker = $slot->worker;
        $t = 'notifications.booking_pending_client';

        $confirmUrl = URL::signedRoute('bookings.confirm-by-link', [
            'token' => $this->booking->token,
            'action' => 'confirm',
        ]);

        $rejectUrl = URL::signedRoute('bookings.confirm-by-link', [
            'token' => $this->booking->token,
            'action' => 'reject',
        ]);

        $contact = $this->booking->customer_phone ?? $this->booking->customer_email ?? '—';

        $mail = (new MailMessage)
            ->theme('green')
            ->subject(__("{$t}.subject", ['name' => $this->booking->customer_name]))
            ->greeting(__("{$t}.greeting"))
            ->line(__("{$t}.line"))
            ->line(__("{$t}.customer", ['name' => $this->booking->customer_name]))
            ->line(__("{$t}.contact", ['contact' => $contact]))
            ->line(__("{$t}.worker", ['name' => $worker->name]));

        if ($this->booking->service) {
            $mail->line(__("{$t}.service", ['name' => $this->booking->service->labelWithPrice()]));
        }

        $mail->line(__("{$t}.date", ['date' => $slot->date->format('d.m.Y')]))
            ->line(__("{$t}.time", ['start' => substr($this->booking->start_time, 0, 5), 'end' => substr($this->booking->end_time, 0, 5)]));

        if ($this->booking->note) {
            $mail->line(__("{$t}.note", ['note' => $this->booking->note]));
        }

        return $mail
            ->action(__("{$t}.action_confirm"), $confirmUrl)
            ->line('['.__("{$t}.action_reject").']('.$rejectUrl.')')
            ->salutation(__("{$t}.salutation"));
    }
}
