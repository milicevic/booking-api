<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\Messages\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BookingConfirmedClient extends Notification
{
    public function __construct(
        private Booking $booking
    ) {}

    /**
     * @return list<string>
     */
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
            'type' => 'booking_confirmed',
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
        $t = 'notifications.booking_confirmed_client_push';

        return (new WebPushMessage)
            ->title(__("{$t}.title", ['name' => $this->booking->customer_name]))
            ->body(__("{$t}.body", [
                'name' => $this->booking->customer_name,
                'date' => $slot->date->format('d.m.Y'),
                'start' => substr($this->booking->start_time, 0, 5),
            ]))
            ->icon('/icon.png');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = 'notifications.booking_confirmed_client';

        return BrandedMailMessage::for($this->booking->tenant ?? $this->booking->slot->worker->tenant)
            ->subject(__("{$t}.subject", ['name' => $this->booking->customer_name]))
            ->status(BookingStatus::Confirmed)
            ->greeting(__("{$t}.greeting"))
            ->line(__("{$t}.line"))
            ->bookingDetails($this->booking, withCustomer: true)
            ->action(__("{$t}.action"), config('app.frontend_url').'/admin');
    }
}
