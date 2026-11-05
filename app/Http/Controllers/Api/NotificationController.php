<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Http\Resources\NotificationResource;
use App\Services\Contracts\UserNotificationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(private readonly UserNotificationServiceInterface $notifications) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection($this->notifications->list($request->user()));
    }

    public function markRead(Request $request, string $id): MessageResource
    {
        $this->notifications->markRead($request->user(), $id);

        return MessageResource::make(__('messages.notification_read'));
    }

    public function markAllRead(Request $request): MessageResource
    {
        $this->notifications->markAllRead($request->user());

        return MessageResource::make(__('messages.notifications_all_read'));
    }
}
