<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ConfirmByLinkRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Push\SubscribePushRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\MessageResource;
use App\Services\Contracts\BookingServiceInterface;
use App\Services\Contracts\PushSubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly BookingServiceInterface $bookings) {}

    /** Admin/Worker/Klijent — rezervacije koje korisnik sme da vidi */
    public function index(Request $request): AnonymousResourceCollection
    {
        return BookingResource::collection(
            $this->bookings->listFor($request->user(), $request->integer('client_id') ?: null)
        );
    }

    /** Javno — kupac kreira rezervaciju */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        /** @var array{slot_id: int, start_time?: ?string, service_id?: ?int, customer_name: string, customer_email?: ?string, customer_phone?: ?string, note?: ?string} $data */
        $data = $request->validated();

        return BookingResource::make($this->bookings->create($data))
            ->response()
            ->setStatusCode(201);
    }

    /** Javno — kupac vidi svoju rezervaciju preko tokena */
    public function show(string $token): BookingResource
    {
        return BookingResource::make($this->bookings->findByToken($token));
    }

    /** Javno — kupac otkazuje preko tokena */
    public function cancel(string $token): MessageResource
    {
        $this->bookings->cancel($token);

        return MessageResource::make(__('messages.booking_cancelled'));
    }

    /** Vlasnik firme potvrđuje rezervaciju */
    public function confirm(string $token): MessageResource
    {
        $booking = $this->bookings->findByToken($token);
        Gate::authorize('confirm', $booking);

        $this->bookings->confirm($booking);

        return MessageResource::make(__('messages.booking_confirmed'));
    }

    /** Vlasnik firme odbija rezervaciju */
    public function reject(string $token): MessageResource
    {
        $booking = $this->bookings->findByToken($token, ['slot.worker']);
        Gate::authorize('reject', $booking);

        $this->bookings->reject($booking);

        return MessageResource::make(__('messages.booking_rejected'));
    }

    /** Javno — potvrda/odbijanje putem potpisanog linka iz emaila */
    public function confirmByLink(ConfirmByLinkRequest $request, string $token): MessageResource
    {
        $action = $request->action();
        $this->bookings->applyLinkAction($token, $action);

        return MessageResource::make($action->successMessage());
    }

    /** Javno — kupac registruje push pretplatu za svoju rezervaciju */
    public function storePushSubscription(SubscribePushRequest $request, PushSubscriptionServiceInterface $push, string $token): JsonResponse
    {
        $push->subscribeBooking(
            $token,
            $request->string('endpoint')->toString(),
            $request->string('keys.p256dh')->toString(),
            $request->string('keys.auth')->toString(),
        );

        return MessageResource::make(__('messages.push_subscribed'))->response()->setStatusCode(201);
    }
}
