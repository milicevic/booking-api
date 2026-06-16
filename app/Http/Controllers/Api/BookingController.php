<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingPendingClient;
use App\Notifications\BookingPendingCustomer;
use App\Notifications\BookingRejectedCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /** Admin/Worker — rezervacije */
    public function index(Request $request)
    {
        if ($request->user()->role === 'admin' && $request->client_id) {
            $workerIds = User::where('client_id', $request->client_id)->where('role', 'worker')->pluck('id');

            $bookings = Booking::with('slot.worker')
                ->whereHas('slot', fn ($q) => $q->whereIn('worker_id', $workerIds))
                ->latest()
                ->get();

            return response()->json($bookings);
        }

        if ($request->user()->role === 'worker') {
            $bookings = Booking::with('slot.worker')
                ->whereHas('slot', fn ($q) => $q->where('worker_id', $request->user()->id))
                ->latest()
                ->get();

            return response()->json($bookings);
        }

        $workerIds = $request->user()->workers()->pluck('id');

        $bookings = Booking::with('slot.worker')
            ->whereHas('slot', fn ($q) => $q->whereIn('worker_id', $workerIds))
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    /** Javno — korisnik kreira rezervaciju */
    public function store(Request $request)
    {
        $data = $request->validate([
            'slot_id' => 'required|exists:slots,id',
            'service_id' => 'nullable|exists:services,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $booking = DB::transaction(function () use ($data) {
            $slot = Slot::lockForUpdate()->findOrFail($data['slot_id']);

            abort_if(! $slot->is_available, 409, __('messages.slot_unavailable'));

            if (! empty($data['service_id'])) {
                $serviceForWorker = Service::where('id', $data['service_id'])
                    ->where('is_active', true)
                    ->whereHas('workers', fn ($q) => $q->where('users.id', $slot->worker_id))
                    ->exists();

                abort_if(! $serviceForWorker, 422, __('messages.service_unavailable_for_worker'));
            }

            $slot->update(['is_available' => false]);

            return Booking::create(array_merge($data, ['status' => 'pending']));
        });

        $booking->load('slot.worker.client.clientProfile');
        $client = $booking->slot->worker->client;
        $autoConfirm = $client->clientProfile?->auto_confirm_bookings ?? false;

        if ($autoConfirm) {
            $booking->update(['status' => 'confirmed']);

            if ($booking->customer_email) {
                $booking->notify(new BookingConfirmedCustomer($booking));
            }

            $client->notify(new BookingConfirmedClient($booking));
        } else {
            if ($booking->customer_email) {
                $booking->notify(new BookingPendingCustomer($booking));
            }

            $client->notify(new BookingPendingClient($booking));
        }

        return response()->json($booking->load('slot.worker'), 201);
    }

    /** Javno — korisnik vidi svoju rezervaciju via token */
    public function show(string $token)
    {
        $booking = Booking::with('slot.worker')
            ->where('token', $token)
            ->firstOrFail();

        return response()->json($booking);
    }

    /** Javno — korisnik otkazuje via token */
    public function cancel(string $token)
    {
        $booking = Booking::with('slot')
            ->where('token', $token)
            ->whereIn('status', ['pending', 'confirmed'])
            ->firstOrFail();

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            $booking->slot->update(['is_available' => true]);
        });

        return response()->json(['message' => __('messages.booking_cancelled')]);
    }

    /** Auth (klijent) — potvrđuje rezervaciju */
    public function confirm(Request $request, string $token)
    {
        $booking = Booking::with('slot.worker')
            ->where('token', $token)
            ->firstOrFail();

        abort_if(
            $booking->slot->worker->client_id !== $request->user()->id,
            403,
            __('messages.booking_not_accessible')
        );

        abort_if($booking->status !== 'pending', 422, __('messages.booking_not_pending'));

        $booking->update(['status' => 'confirmed']);

        if ($booking->customer_email) {
            $booking->notify(new BookingConfirmedCustomer($booking));
        }

        return response()->json(['message' => __('messages.booking_confirmed')]);
    }

    /** Auth (klijent) — odbija rezervaciju */
    public function reject(Request $request, string $token)
    {
        $booking = Booking::with('slot')
            ->where('token', $token)
            ->firstOrFail();

        abort_if(
            $booking->slot->worker->client_id !== $request->user()->id,
            403,
            __('messages.booking_not_accessible')
        );

        abort_if($booking->status !== 'pending', 422, __('messages.booking_not_pending'));

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'rejected']);
            $booking->slot->update(['is_available' => true]);
        });

        if ($booking->customer_email) {
            $booking->notify(new BookingRejectedCustomer($booking));
        }

        return response()->json(['message' => __('messages.booking_rejected')]);
    }

    /** Javno — potvrda/odbijanje putem signed linka iz emaila */
    public function confirmByLink(Request $request, string $token)
    {
        abort_if(! $request->hasValidSignature(), 403, __('messages.invalid_link'));

        $action = $request->query('action');
        abort_if(! in_array($action, ['confirm', 'reject']), 422, __('messages.invalid_action'));

        $booking = Booking::with('slot')
            ->where('token', $token)
            ->firstOrFail();

        abort_if($booking->status !== 'pending', 422, __('messages.booking_not_pending'));

        if ($action === 'confirm') {
            $booking->update(['status' => 'confirmed']);

            if ($booking->customer_email) {
                $booking->notify(new BookingConfirmedCustomer($booking));
            }

            return response()->json(['message' => __('messages.booking_confirmed')]);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'rejected']);
            $booking->slot->update(['is_available' => true]);
        });

        if ($booking->customer_email) {
            $booking->notify(new BookingRejectedCustomer($booking));
        }

        return response()->json(['message' => __('messages.booking_rejected')]);
    }
}
