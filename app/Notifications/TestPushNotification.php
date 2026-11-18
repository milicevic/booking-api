<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Probno push obaveštenje (komanda push:test), da se na serveru proveri podešavanje VAPID ključeva.
 */
class TestPushNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title(__('notifications.test_push.title'))
            ->body(__('notifications.test_push.body', ['app_name' => config('app.name')]))
            ->icon('/icon-192.png')
            ->data(['url' => '/admin']);
    }
}
