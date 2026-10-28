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

            $bookings = Booking::with(['slot.worker', 'service'])
                ->whereHas('slot', fn ($q) => $q->whereIn('worker_id', $workerIds))
                ->latest()
                ->get();

            return response()->json($bookings);
        }

        if ($request->user()->role === 'worker') {
            $bookings = Booking::with(['slot.worker', 'service'])
                ->whereHas('slot', fn ($q) => $q->where('worker_id', $request->user()->id))
                ->latest()
                ->get();

            return response()->json($bookings);
        }

        $workerIds = $request->user()->workers()->pluck('id');

        $bookings = Booking::with(['slot.worker', 'service'])
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
            'start_time' => 'nullable|date_format:H:i',
            'service_id' => 'nullable|exists:services,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $booking = DB::transaction(function () use ($data) {
            $slot = Slot::findOrFail($data['slot_id']);

            // Zaključavamo sve termine radnika tog dana, da dve istovremene rezervacije ne zauzmu isto vreme
            Slot::where('worker_id', $slot->worker_id)->whereDate('date', $slot->date)->lockForUpdate()->get();

            $workerHasServices = Service::where('is_active', true)
                ->whereHas('workers', fn ($q) => $q->where('users.id', $slot->worker_id))
                ->exists();

            abort_if($workerHasServices && empty($data['service_id']), 422, __('messages.service_required'));

            $service = null;
            if (! empty($data['service_id'])) {
                $service = Service::where('id', $data['service_id'])
                    ->where('is_active', true)
                    ->whereHas('workers', fn ($q) => $q->where('users.id', $slot->worker_id))
                    ->first();

                abort_if(! $service, 422, __('messages.service_unavailable_for_worker'));
            }

            $startTime = isset($data['start_time']) ? "{$data['start_time']}:00" : $slot->start_time;
            $time = collect(Slot::bookableTimes($slot->worker_id, $service?->duration_minutes, $slot->date->toDateString()))
                ->first(fn (array $time) => $time['start_time'] === $startTime);

            abort_if(! $time, 409, __('messages.slot_unavailable'));

            $booking = Booking::create(array_merge($data, [
                'slot_id' => $time['slot_id'],
                'start_time' => $time['start_time'],
                'end_time' => $time['end_time'],
                'status' => 'pending',
                'locale' => app()->getLocale(),
            ]));

            Slot::refreshAvailability($slot->worker_id, $slot->date->toDateString());

            return $booking;
        });

        $booking->load('slot.worker.client.clientProfile');
        $client = $booking->slot->worker->client;
        $autoConfirm = $client->clientProfile?->auto_confirm_bookings ?? false;

        if ($autoConfirm) {
            $booking->update(['status' => 'confirmed']);
            $booking->notify(new BookingConfirmedCustomer($booking));
            $client->notify(new BookingConfirmedClient($booking));
        } else {
            $booking->notify(new BookingPendingCustomer($booking));
            $client->notify(new BookingPendingClient($booking));
        }

        return response()->json($booking->load(['slot.worker', 'service']), 201);
    }

    /** Javno — korisnik vidi svoju rezervaciju via token */
    public function show(string $token)
    {
        $booking = Booking::with(['slot.worker', 'service'])
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
            Slot::refreshAvailability($booking->slot->worker_id, $booking->slot->date->toDateString());
        });

        return response()->json(['message' => __('messages.booking_cancelled')]);
    }

    /** Auth (klijent) — potvrđuje rezervaciju */
    public function confirm(Request $request, string $token)
    {
        $booking = Booking::with(['slot.worker', 'service'])
            ->where('token', $token)
            ->firstOrFail();

        abort_if(
            $booking->slot->worker->client_id !== $request->user()->id,
            403,
            __('messages.booking_not_accessible')
        );

        abort_if($booking->status !== 'pending', 422, __('messages.booking_not_pending'));

        $booking->update(['status' => 'confirmed']);
        $booking->notify(new BookingConfirmedCustomer($booking));

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
            Slot::refreshAvailability($booking->slot->worker_id, $booking->slot->date->toDateString());
        });

        $booking->notify(new BookingRejectedCustomer($booking));

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
            $booking->notify(new BookingConfirmedCustomer($booking));

            return response()->json(['message' => __('messages.booking_confirmed')]);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'rejected']);
            Slot::refreshAvailability($booking->slot->worker_id, $booking->slot->date->toDateString());
        });

        $booking->notify(new BookingRejectedCustomer($booking));

        return response()->json(['message' => __('messages.booking_rejected')]);
    }

    /** Javno — korisnik registruje push subscription za svoju rezervaciju */
    public function storePushSubscription(Request $request, string $token)
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $booking = Booking::where('token', $token)->firstOrFail();

        $booking->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
        );

        return response()->json(['message' => __('messages.push_subscribed')], 201);
    }
}
