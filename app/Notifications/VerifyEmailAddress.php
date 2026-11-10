<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Notifications\Messages\BrandedMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailAddress extends Notification
{
    use Queueable;

    public const LINK_TTL_HOURS = 48;

    public function __construct(public readonly Tenant $tenant) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verifyUrl = URL::temporarySignedRoute('verification.verify', now()->addHours(self::LINK_TTL_HOURS), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
        $t = 'notifications.verify_email';

        return BrandedMailMessage::for($this->tenant)
            ->subject(__("{$t}.subject", ['app_name' => $this->tenant->app_name]))
            ->greeting(__("{$t}.greeting", ['name' => $notifiable->name]))
            ->line(__("{$t}.line1", ['app_name' => $this->tenant->app_name]))
            ->action(__("{$t}.action"), $verifyUrl)
            ->line(__("{$t}.line2", ['hours' => self::LINK_TTL_HOURS]));
    }
}
