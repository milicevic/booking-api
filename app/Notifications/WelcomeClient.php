<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeClient extends Notification
{
    use Queueable;

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
        $loginUrl = "https://{$this->tenant->subdomain}.booking.app/login";
        $trialEndsAt = $this->tenant->trial_ends_at?->format('d.m.Y') ?? '—';
        $t = 'notifications.welcome_client';

        return (new MailMessage)
            ->subject(__("{$t}.subject", ['app_name' => $this->tenant->app_name]))
            ->greeting(__("{$t}.greeting", ['name' => $notifiable->name]))
            ->line(__("{$t}.line1", ['app_name' => $this->tenant->app_name]))
            ->line(__("{$t}.line2", ['date' => $trialEndsAt]))
            ->action(__("{$t}.action"), $loginUrl)
            ->line(__("{$t}.line3"))
            ->line(__("{$t}.line4"));
    }
}
