<?php

namespace App\Console\Commands;

use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Console\Command;

/**
 * Rezervacije koje vlasnik nije potvrdio na vreme (pending_expiry_hours) ili čiji je termin već počeo
 * se odbijaju, a termin se oslobađa. Kupac dobija mejl kao pri odbijanju.
 */
class ExpirePendingBookings extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Reject bookings that were not confirmed in time and free their slots';

    public function handle(BookingServiceInterface $bookings): int
    {
        $count = $bookings->expirePending();

        $this->info("Rejected {$count} unconfirmed booking(s).");

        return self::SUCCESS;
    }
}
