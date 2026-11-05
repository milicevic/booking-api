<?php

namespace App\Services\Push;

use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Contracts\PushSubscriptionServiceInterface;

class PushSubscriptionService implements PushSubscriptionServiceInterface
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    public function vapidPublicKey(): ?string
    {
        $key = config('webpush.vapid.public_key');

        return is_string($key) ? $key : null;
    }

    public function subscribeUser(User $user, string $endpoint, string $publicKey, string $authToken): void
    {
        $user->updatePushSubscription($endpoint, $publicKey, $authToken);
    }

    public function unsubscribeUser(User $user, string $endpoint): void
    {
        $user->deletePushSubscription($endpoint);
    }

    public function subscribeBooking(string $bookingToken, string $endpoint, string $publicKey, string $authToken): void
    {
        $this->bookings->findByTokenOrFail($bookingToken)->updatePushSubscription($endpoint, $publicKey, $authToken);
    }
}
