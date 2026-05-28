<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingRejectedCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
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

    private function signedUrl(string $token, string $action): string
    {
        return URL::signedRoute('bookings.confirm-by-link', [
            'token' => $token,
            'action' => $action,
        ]);
    }

    public function test_confirm_via_signed_link_sets_status_confirmed(): void
    {
        Notification::fake();

        $booking = $this->setupBooking('pending');

        $response = $this->getJson($this->signedUrl($booking->token, 'confirm'));

        $response->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_customer_receives_confirmed_notification_via_signed_link(): void
    {
        Notification::fake();

        $booking = $this->setupBooking('pending');

        $this->getJson($this->signedUrl($booking->token, 'confirm'));

        Notification::assertSentTo($booking, BookingConfirmedCustomer::class);
    }

    public function test_reject_via_signed_link_sets_status_rejected(): void
    {
        Notification::fake();

        $booking = $this->setupBooking('pending');

        $response = $this->getJson($this->signedUrl($booking->token, 'reject'));

        $response->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'rejected']);
    }

    public function test_slot_is_freed_after_reject_via_signed_link(): void
    {
        Notification::fake();

        $booking = $this->setupBooking('pending');
        $slotId = $booking->slot_id;

        $this->getJson($this->signedUrl($booking->token, 'reject'));

        $this->assertDatabaseHas('slots', ['id' => $slotId, 'is_available' => true]);
    }

    public function test_customer_receives_rejected_notification_via_signed_link(): void
    {
        Notification::fake();

        $booking = $this->setupBooking('pending');

        $this->getJson($this->signedUrl($booking->token, 'reject'));

        Notification::assertSentTo($booking, BookingRejectedCustomer::class);
    }

    public function test_returns_403_with_invalid_signature(): void
    {
        $booking = $this->setupBooking('pending');

        $response = $this->getJson("/api/bookings/{$booking->token}/confirm-by-link?action=confirm&signature=invalid");

        $response->assertStatus(403);
    }

    public function test_returns_403_with_tampered_url(): void
    {
        $booking = $this->setupBooking('pending');
        $url = $this->signedUrl($booking->token, 'confirm');

        // Promijeni action ali zadrži originalni signature
        $tamperedUrl = str_replace('action=confirm', 'action=reject', $url);

        $response = $this->getJson($tamperedUrl);

        $response->assertStatus(403);
    }

    public function test_returns_422_when_booking_is_not_pending(): void
    {
        Notification::fake();

        $booking = $this->setupBooking('confirmed');

        $response = $this->getJson($this->signedUrl($booking->token, 'confirm'));

        $response->assertStatus(422);
    }

    public function test_returns_422_with_invalid_action(): void
    {
        $booking = $this->setupBooking('pending');

        $response = $this->getJson($this->signedUrl($booking->token, 'invalid-action'));

        $response->assertStatus(422);
    }
}
