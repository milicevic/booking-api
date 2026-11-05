<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function latestForUser(User $user): DatabaseNotificationCollection
    {
        return $user->notifications()->latest()->get();
    }

    public function findForUserOrFail(User $user, string $id): DatabaseNotification
    {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($id);

        return $notification;
    }

    public function markAllReadForUser(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
