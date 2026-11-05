<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\Contracts\UserNotificationServiceInterface;
use Illuminate\Notifications\DatabaseNotificationCollection;

class UserNotificationService implements UserNotificationServiceInterface
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function list(User $user): DatabaseNotificationCollection
    {
        return $this->notifications->latestForUser($user);
    }

    public function markRead(User $user, string $notificationId): void
    {
        $this->notifications->findForUserOrFail($user, $notificationId)->markAsRead();
    }

    public function markAllRead(User $user): void
    {
        $this->notifications->markAllReadForUser($user);
    }
}
