<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Authenticated user — lista notifikacija */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->get();

        return response()->json($notifications);
    }

    /** Authenticated user — označi notifikaciju kao pročitanu */
    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => __('messages.notification_read')]);
    }

    /** Authenticated user — označi sve notifikacije kao pročitane */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => __('messages.notifications_all_read')]);
    }
}
