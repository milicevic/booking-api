<?php

namespace App\Services\Contracts;

use App\Enums\BookingLinkAction;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface BookingServiceInterface
{
    /**
     * Rezervacije koje korisnik sme da vidi (admin bira klijenta, radnik vidi svoje, klijent svoje radnike).
     *
     * @return Collection<int, Booking>
     */
    public function listFor(User $user, ?int $clientId = null): Collection;

    /**
     * @param  array{slot_id: int, start_time?: ?string, service_id?: ?int, customer_name: string, customer_email?: ?string, customer_phone?: ?string, note?: ?string}  $data
     */
    public function create(array $data): Booking;

    /**
     * @param  list<string>  $with
     */
    public function findByToken(string $token, array $with = ['slot.worker', 'service']): Booking;

    public function cancel(string $token, bool $notifyCustomer = false): Booking;

    public function confirm(Booking $booking): void;

    public function reject(Booking $booking): void;

    /** Potvrda ili odbijanje preko potpisanog linka iz mejla. */
    public function applyLinkAction(string $token, BookingLinkAction $action): Booking;
}
