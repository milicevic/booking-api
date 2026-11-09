<?php

namespace App\Http\Controllers;

use App\Enums\BookingLinkAction;
use App\Exceptions\DomainException;
use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Potvrda ili odbijanje rezervacije iz mejla vlasniku.
 *
 * Link iz mejla (potpisan, ističe) samo otvara stranicu sa podacima i dugmićima; akcija se izvršava
 * tek klikom (POST). Programi koji skeniraju linkove u mejlovima zato ne mogu sami da potvrde ili odbiju.
 */
class BookingReviewController extends Controller
{
    /** Koliko dugo važi link iz mejla. */
    public const LINK_TTL_DAYS = 7;

    public function __construct(private readonly BookingServiceInterface $bookings) {}

    public static function signedUrl(string $token, ?BookingLinkAction $action = null): string
    {
        return URL::temporarySignedRoute('booking.review', now()->addDays(self::LINK_TTL_DAYS), array_filter([
            'token' => $token,
            'action' => $action?->value,
        ]));
    }

    public function show(Request $request, string $token): View
    {
        $booking = $this->bookings->findByToken($token, ['slot.worker', 'service']);

        return view('booking.review', [
            'booking' => $booking,
            'suggested' => BookingLinkAction::tryFrom($request->string('action')->toString()),
            'applyUrl' => URL::temporarySignedRoute('booking.review.apply', now()->addHour(), ['token' => $token]),
        ]);
    }

    public function apply(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::enum(BookingLinkAction::class)],
        ]);
        $action = BookingLinkAction::from($data['action']);

        try {
            $this->bookings->applyLinkAction($token, $action);
            $flash = ['success' => $action->successMessage()];
        } catch (DomainException $e) {
            $flash = ['error' => $e->getMessage()];
        }

        return redirect()->to(self::signedUrl($token))->with($flash);
    }
}
