<?php

namespace App\Http\Controllers;

use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Stranica (Blade) na kojoj kupac vidi i otkazuje rezervaciju, kada sajt firme nije dostupan.
 */
class ManageBookingController extends Controller
{
    public function __construct(private readonly BookingServiceInterface $bookings) {}

    public function show(string $token): View
    {
        $booking = $this->bookings->findByToken($token, ['slot.worker', 'service', 'tenant']);

        return view('booking.manage', compact('booking'));
    }

    public function cancel(string $token): RedirectResponse
    {
        $this->bookings->cancel($token, notifyCustomer: true);

        return redirect()->route('booking.manage', $token)
            ->with('success', __('manage.cancelled_success'));
    }
}
