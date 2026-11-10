<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Messages\BrandedMailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordLink extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $query = http_build_query(['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $path = "/admin/reset-password?{$query}";
        // Link vodi na sajt firme korisnika (platformski korisnici idu na glavni frontend)
        $resetUrl = $notifiable->tenant
            ? $notifiable->tenant->siteUrl($path)
            : rtrim((string) config('app.frontend_url'), '/').$path;
        $t = 'notifications.reset_password';

        return BrandedMailMessage::for($notifiable->tenant)
            ->subject(__("{$t}.subject"))
            ->greeting(__("{$t}.greeting", ['name' => $notifiable->name]))
            ->line(__("{$t}.line1"))
            ->action(__("{$t}.action"), $resetUrl)
            ->line(__("{$t}.line2", ['minutes' => config('auth.passwords.users.expire')]));
    }
}
