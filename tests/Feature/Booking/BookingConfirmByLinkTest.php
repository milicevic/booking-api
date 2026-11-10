<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingLinkAction;
use App\Http\Controllers\BookingReviewController;
use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingRejectedCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BookingConfirmByLinkTest extends TestCase
{
    use RefreshDatabase;

    private function setupBooking(string $status = 'pending'): Booking
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => false]);

        return Booking::factory()->create(['slot_id' => $slot->id, 'status' => $status]);
    }

    /** Klik na dugme na stranici: POST na potpisanu adresu iz stranice */
    private function apply(Booking $booking, string $action): TestResponse
    {
        $url = URL::temporarySignedRoute('booking.review.apply', now()->addHour(), ['token' => $booking->token]);

        return $this->post($url, ['action' => $action]);
    }

    public function test_opening_the_mail_link_does_not_change_the_booking(): void
    {
        Notification::fake();
        $booking = $this->setupBooking();

        // Ovo rade i programi koji skeniraju linkove u mejlovima
        $this->get(BookingReviewController::signedUrl($booking->token, BookingLinkAction::Reject))
            ->assertOk()
            ->assertSee($booking->customer_name)
            ->assertSee('name="action" value="confirm"', false)
            ->assertSee('name="action" value="reject"', false);

        $this->assertSame('pending', $booking->fresh()->status->value);
        Notification::assertNothingSent();
    }

    public function test_confirm_button_confirms_and_notifies_the_customer(): void
    {
        Notification::fake();
        $booking = $this->setupBooking();

        $this->apply($booking, 'confirm')->assertRedirect();

        $this->assertSame('confirmed', $booking->fresh()->status->value);
        Notification::assertSentTo($booking, BookingConfirmedCustomer::class);
    }

    public function test_reject_button_rejects_frees_the_slot_and_notifies(): void
    {
        Notification::fake();
        $booking = $this->setupBooking();

        $this->apply($booking, 'reject')->assertRedirect();

        $this->assertSame('rejected', $booking->fresh()->status->value);
        $this->assertDatabaseHas('slots', ['id' => $booking->slot_id, 'is_available' => true]);
        Notification::assertSentTo($booking, BookingRejectedCustomer::class);
    }

    public function test_mail_link_expires_after_seven_days(): void
    {
        $booking = $this->setupBooking();
        $url = BookingReviewController::signedUrl($booking->token, BookingLinkAction::Confirm);

        $this->travel(8)->days();

        $this->get($url)->assertForbidden();
    }

    public function test_tampered_or_unsigned_requests_are_rejected(): void
    {
        $booking = $this->setupBooking();
        $url = BookingReviewController::signedUrl($booking->token);

        $this->get(str_replace($booking->token, 'drugi-token', $url))->assertForbidden();
        $this->post("/bookings/{$booking->token}/review", ['action' => 'confirm'])->assertForbidden();

        $this->assertSame('pending', $booking->fresh()->status->value);
    }

    public function test_booking_that_is_no_longer_pending_shows_an_error(): void
    {
        Notification::fake();
        $booking = $this->setupBooking('confirmed');

        $this->apply($booking, 'reject')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $booking->fresh()->status->value);
    }

    public function test_invalid_action_is_rejected(): void
    {
        $booking = $this->setupBooking();

        $this->apply($booking, 'nesto')->assertSessionHasErrors('action');

        $this->assertSame('pending', $booking->fresh()->status->value);
    }

    public function test_old_api_links_only_lead_to_the_review_page(): void
    {
        Notification::fake();
        $booking = $this->setupBooking();
        $oldLink = URL::signedRoute('bookings.confirm-by-link', ['token' => $booking->token, 'action' => 'confirm']);

        $location = $this->get($oldLink)->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString("/bookings/{$booking->token}/review", (string) $location);
        $this->assertSame('pending', $booking->fresh()->status->value);
        $this->getJson("/api/bookings/{$booking->token}/confirm-by-link?action=confirm&signature=invalid")->assertForbidden();
    }
}
