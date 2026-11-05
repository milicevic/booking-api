<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Push\SubscribePushRequest;
use App\Http\Requests\Push\UnsubscribePushRequest;
use App\Http\Resources\MessageResource;
use App\Services\Contracts\PushSubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;

class PushController extends Controller
{
    public function __construct(private readonly PushSubscriptionServiceInterface $push) {}

    /** VAPID javni ključ, potreban pregledaču za pretplatu. */
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json(['vapid_public_key' => $this->push->vapidPublicKey()]);
    }

    public function subscribe(SubscribePushRequest $request): JsonResponse
    {
        $this->push->subscribeUser(
            $request->user(),
            $request->string('endpoint')->toString(),
            $request->string('keys.p256dh')->toString(),
            $request->string('keys.auth')->toString(),
        );

        return MessageResource::make(__('messages.push_subscribed'))->response()->setStatusCode(201);
    }

    public function unsubscribe(UnsubscribePushRequest $request): MessageResource
    {
        $this->push->unsubscribeUser($request->user(), $request->string('endpoint')->toString());

        return MessageResource::make(__('messages.push_unsubscribed'));
    }
}
