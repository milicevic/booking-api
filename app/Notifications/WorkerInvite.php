<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkerInvite extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $inviteToken,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = "https://{$this->tenant->subdomain}.booking.app/accept-invite?token={$this->inviteToken}";
        $t = 'notifications.worker_invite';

        return (new MailMessage)
            ->subject(__("{$t}.subject", ['app_name' => $this->tenant->app_name]))
            ->greeting(__("{$t}.greeting", ['name' => $notifiable->name]))
            ->line(__("{$t}.line1", ['app_name' => $this->tenant->app_name]))
            ->action(__("{$t}.action"), $acceptUrl)
            ->line(__("{$t}.line2"))
            ->line(__("{$t}.line3"));
    }
}
