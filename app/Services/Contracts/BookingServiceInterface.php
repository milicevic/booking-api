<?php

namespace App\Services\Contracts;

use App\Enums\BookingLinkAction;
use App\Models\BlockedCustomer;
use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookingServiceInterface
{
    /**
     * Rezervacije koje korisnik sme da vidi (admin bira klijenta, radnik vidi svoje, klijent svoje radnike).
     * Filter worker_id ne može da proširi taj skup.
     *
     * @param  array{from?: ?string, to?: ?string, worker_id?: ?int, status?: ?string}  $filters
     * @return Collection<int, Booking>|LengthAwarePaginator<int, Booking>
     */
    public function listFor(User $user, ?int $clientId = null, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator;

    /**
     * @param  array{slot_id: int, start_time?: ?string, service_id: int, customer_name: string, customer_email?: ?string, customer_phone?: ?string, note?: ?string}  $data
     */
    public function create(array $data): Booking;

    /**
     * @param  list<string>  $with
     */
    public function findByToken(string $token, array $with = ['slot.worker', 'service']): Booking;

    public function cancel(string $token, bool $notifyCustomer = false): Booking;

    public function confirm(Booking $booking): void;

    public function reject(Booking $booking): void;

    /**
     * Pomera aktivnu rezervaciju na termin $target u $startTime (H:i); provera kao pri zakazivanju,
     * u transakciji sa zaključanim danima radnika. Kupac dobija obaveštenje.
     */
    public function reschedule(Booking $booking, Slot $target, string $startTime): Booking;

    /**
     * Kupac (po tokenu) pomera svoju aktivnu rezervaciju na jedno od ponuđenih vremena, najkasnije
     * rescheduleCutoffHours() pre početka. Van režima "auto" rezervacija ponovo čeka potvrdu.
     */
    public function rescheduleByCustomer(string $token, int $slotId, string $startTime): Booking;

    /**
     * Vlasnik blokira kupca iz rezervacije (telefon i email): nove rezervacije se odbijaju, a sve njegove
     * buduće aktivne rezervacije u firmi se otkazuju (kupac dobija mejl).
     *
     * @return array{blocked: BlockedCustomer, cancelled: int}
     */
    public function blockCustomer(Booking $booking, User $owner): array;

    /**
     * Odbija rezervacije koje čekaju potvrdu duže od pending_expiry_hours ili čiji je termin počeo,
     * i oslobađa vreme (komanda bookings:expire-pending). Vraća broj odbijenih.
     */
    public function expirePending(): int;

    /** Potvrda ili odbijanje preko potpisanog linka iz mejla. */
    public function applyLinkAction(string $token, BookingLinkAction $action): Booking;
}
