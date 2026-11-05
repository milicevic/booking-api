<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;

interface NotificationRepositoryInterface
{
    /**
     * @return DatabaseNotificationCollection<int, DatabaseNotification>
     */
    public function latestForUser(User $user): DatabaseNotificationCollection;

    public function findForUserOrFail(User $user, string $id): DatabaseNotification;

    public function markAllReadForUser(User $user): void;
}
