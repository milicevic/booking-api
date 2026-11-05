<?php

namespace App\Services\Contracts;

use App\Models\User;

interface PushSubscriptionServiceInterface
{
    public function vapidPublicKey(): ?string;

    public function subscribeUser(User $user, string $endpoint, string $publicKey, string $authToken): void;

    public function unsubscribeUser(User $user, string $endpoint): void;

    /** Kupac (bez naloga) prima push obaveštenja za svoju rezervaciju. */
    public function subscribeBooking(string $bookingToken, string $endpoint, string $publicKey, string $authToken): void;
}
