<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BookingReviewController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ConfirmByLinkRequest;
use App\Http\Requests\Booking\ListBookingsRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Push\SubscribePushRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\PublicBookingResource;
use App\Services\Contracts\BookingCalendarInterface;
use App\Services\Contracts\BookingServiceInterface;
use App\Services\Contracts\PushSubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly BookingServiceInterface $bookings) {}

    /** Admin/Worker/Klijent — rezervacije koje korisnik sme da vidi; filteri from, to, worker_id, status i opciono per_page */
    public function index(ListBookingsRequest $request): AnonymousResourceCollection
    {
        return BookingResource::collection(
            $this->bookings->listFor($request->user(), $request->integer('client_id') ?: null, $request->filters(), $request->perPage())
        );
    }

    /** Javno — kupac kreira rezervaciju */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        /** @var array{slot_id: int, start_time?: ?string, service_id?: ?int, customer_name: string, customer_email?: ?string, customer_phone?: ?string, note?: ?string} $data */
        $data = $request->validated();

        return PublicBookingResource::make($this->bookings->create($data))
            ->response()
            ->setStatusCode(201);
    }

    /** Javno — kupac vidi svoju rezervaciju preko tokena */
    public function show(string $token): PublicBookingResource
    {
        return PublicBookingResource::make($this->bookings->findByToken($token));
    }

    /** Javno — kupac pomera svoju rezervaciju na jedno od ponuđenih vremena (najkasnije rok pre početka) */
    public function customerReschedule(RescheduleBookingRequest $request, string $token): PublicBookingResource
    {
        return PublicBookingResource::make($this->bookings->rescheduleByCustomer(
            $token,
            $request->integer('slot_id'),
            $request->string('start_time')->toString(),
        ));
    }

    /** Javno — kupac otkazuje preko tokena */
    public function cancel(string $token): MessageResource
    {
        $this->bookings->cancel($token, notifyCustomer: true);

        return MessageResource::make(__('messages.booking_cancelled'));
    }

    /** Vlasnik firme (ili radnik u režimu potvrde "worker") potvrđuje rezervaciju */
    public function confirm(string $token): MessageResource
    {
        $booking = $this->bookings->findByToken($token);
        Gate::authorize('confirm', $booking);

        $this->bookings->confirm($booking);

        return MessageResource::make(__('messages.booking_confirmed'));
    }

    /** Vlasnik firme (ili radnik u režimu potvrde "worker") odbija rezervaciju */
    public function reject(string $token): MessageResource
    {
        $booking = $this->bookings->findByToken($token, ['slot.worker']);
        Gate::authorize('reject', $booking);

        $this->bookings->reject($booking);

        return MessageResource::make(__('messages.booking_rejected'));
    }

    /** Vlasnik (ili radnik u režimu potvrde "worker") pomera rezervaciju na drugi termin */
    public function reschedule(RescheduleBookingRequest $request, string $token): BookingResource
    {
        $booking = $this->bookings->findByToken($token, ['slot.worker', 'service']);
        $target = $request->targetSlot();
        Gate::authorize('reschedule', [$booking, $target]);

        return BookingResource::make(
            $this->bookings->reschedule($booking, $target, $request->string('start_time')->toString())
        );
    }

    /**
     * Stari link iz već poslatih mejlova: više ne menja rezervaciju, nego vodi na stranicu za pregled.
     */
    public function confirmByLink(ConfirmByLinkRequest $request, string $token): RedirectResponse
    {
        return redirect()->away(BookingReviewController::signedUrl($token, $request->action()));
    }

    /** Javno — .ics za kalendar kupca (za otkazanu/odbijenu rezervaciju: otkazivanje događaja) */
    public function calendar(string $token, BookingCalendarInterface $calendar): Response
    {
        $booking = $this->bookings->findByToken($token, ['slot.worker.client.clientProfile', 'service', 'tenant']);

        abort_unless($calendar->enabled($booking), 404);

        return response($calendar->ics($booking), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="termin.ics"',
            'Cache-Control' => 'no-store',
        ]);
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
