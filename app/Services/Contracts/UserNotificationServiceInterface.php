<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;

interface UserNotificationServiceInterface
{
    /**
     * @return DatabaseNotificationCollection<int, DatabaseNotification>
     */
    public function list(User $user): DatabaseNotificationCollection;

    public function markRead(User $user, string $notificationId): void;

    public function markAllRead(User $user): void;
}
