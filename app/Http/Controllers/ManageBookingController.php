<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Notifications\BookingCancelledCustomer;
use Illuminate\Support\Facades\DB;

class ManageBookingController extends Controller
{
    public function show(string $token)
    {
        $booking = Booking::with('slot.worker')
            ->where('token', $token)
            ->firstOrFail();

        return view('booking.manage', compact('booking'));
    }

    public function cancel(string $token)
    {
        $booking = Booking::with('slot')
            ->where('token', $token)
            ->whereIn('status', ['confirmed', 'pending'])
            ->firstOrFail();

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            $booking->slot->update(['is_available' => true]);
        });

        if ($booking->customer_email) {
            $booking->notify(new BookingCancelledCustomer($booking));
        }

        return redirect()->route('booking.manage', $token)
            ->with('success', 'Vaša rezervacija je uspješno otkazana.');
    }
}
