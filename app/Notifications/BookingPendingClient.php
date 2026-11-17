<?php

namespace App\Notifications;

use App\Enums\BookingLinkAction;
use App\Enums\BookingStatus;
use App\Http\Controllers\BookingReviewController;
use App\Models\Booking;
use App\Notifications\Messages\BrandedMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BookingPendingClient extends Notification
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
            'type' => 'booking_pending',
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
        $worker = $slot->worker;
        $t = 'notifications.booking_pending_client_push';

        return (new WebPushMessage)
            ->title(__($this->rescheduled ? "{$t}.title_rescheduled" : "{$t}.title", ['name' => $this->booking->customer_name]))
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
        $t = 'notifications.booking_pending_client';

        // Linkovi otvaraju stranicu za pregled (BookingReviewController); akcija se ne izvršava samim otvaranjem
        $confirmUrl = BookingReviewController::signedUrl($this->booking->token, BookingLinkAction::Confirm);
        $rejectUrl = BookingReviewController::signedUrl($this->booking->token, BookingLinkAction::Reject);

        return BrandedMailMessage::for($this->booking->tenant ?? $this->booking->slot->worker->tenant)
            ->subject(__($this->rescheduled ? "{$t}.subject_rescheduled" : "{$t}.subject", ['name' => $this->booking->customer_name]))
            ->status(BookingStatus::Pending)
            ->greeting(__($this->rescheduled ? "{$t}.greeting_rescheduled" : "{$t}.greeting"))
            ->line(__($this->rescheduled ? "{$t}.line_rescheduled" : "{$t}.line"))
            ->bookingDetails($this->booking, withCustomer: true)
            ->action(__("{$t}.action_confirm"), $confirmUrl)
            ->secondaryAction(__("{$t}.action_reject"), $rejectUrl);
    }
}
