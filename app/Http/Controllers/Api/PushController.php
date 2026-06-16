<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    /** Return the VAPID public key (needed by browser to subscribe). */
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json(['vapid_public_key' => config('webpush.vapid.public_key')]);
    }

    /** Store a push subscription for the authenticated user. */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
        );

        return response()->json(['message' => __('messages.push_subscribed')], 201);
    }

    /** Remove a push subscription. */
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['message' => __('messages.push_unsubscribed')]);
    }
}
