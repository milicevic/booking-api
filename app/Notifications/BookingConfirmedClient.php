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
    /**
     * @param  bool  $rescheduled  kupac je sam pomerio postojeću rezervaciju (drugačiji naslov i tekst)
     */
    public function __construct(
        private Booking $booking,
        private bool $rescheduled = false,
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
            'rescheduled' => $this->rescheduled,
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
            ->title(__($this->rescheduled ? "{$t}.title_rescheduled" : "{$t}.title", ['name' => $this->booking->customer_name]))
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
            ->subject(__($this->rescheduled ? "{$t}.subject_rescheduled" : "{$t}.subject", ['name' => $this->booking->customer_name]))
            ->status(BookingStatus::Confirmed)
            ->greeting(__($this->rescheduled ? "{$t}.greeting_rescheduled" : "{$t}.greeting"))
            ->line(__($this->rescheduled ? "{$t}.line_rescheduled" : "{$t}.line"))
            ->bookingDetails($this->booking, withCustomer: true)
            ->action(__("{$t}.action"), config('app.frontend_url').'/admin');
    }
}
