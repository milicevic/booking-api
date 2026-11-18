<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingCancelledClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingCancelTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(string $status): array
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => false]);
        $booking = Booking::factory()->create(['slot_id' => $slot->id, 'status' => $status]);

        return [$slot, $booking];
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        [$slot, $booking] = $this->makeBooking('pending');

        $response = $this->patchJson("/api/bookings/{$booking->token}/cancel");

        $response->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('slots', ['id' => $slot->id, 'is_available' => true]);
    }

    public function test_customer_can_cancel_confirmed_booking(): void
    {
        [$slot, $booking] = $this->makeBooking('confirmed');

        $response = $this->patchJson("/api/bookings/{$booking->token}/cancel");

        $response->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('slots', ['id' => $slot->id, 'is_available' => true]);
    }

    public function test_slot_becomes_available_after_cancel(): void
    {
        [$slot, $booking] = $this->makeBooking('pending');

        $this->patchJson("/api/bookings/{$booking->token}/cancel");

        $this->assertDatabaseHas('slots', ['id' => $slot->id, 'is_available' => true]);
    }

    public function test_returns_404_when_booking_is_already_cancelled(): void
    {
        [, $booking] = $this->makeBooking('cancelled');

        $response = $this->patchJson("/api/bookings/{$booking->token}/cancel");

        $response->assertNotFound();
    }

    public function test_returns_404_when_booking_is_rejected(): void
    {
        [, $booking] = $this->makeBooking('rejected');

        $response = $this->patchJson("/api/bookings/{$booking->token}/cancel");

        $response->assertNotFound();
    }

    public function test_returns_404_for_invalid_token(): void
    {
        $response = $this->patchJson('/api/bookings/invalid-token/cancel');

        $response->assertNotFound();
    }

    public function test_owner_is_notified_when_customer_cancels(): void
    {
        Notification::fake();
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id]);
        $booking = Booking::factory()->confirmed()->create(['slot_id' => $slot->id, 'customer_name' => 'Ana']);

        $this->patchJson("/api/bookings/{$booking->token}/cancel")->assertOk();

        Notification::assertSentTo($client, BookingCancelledClient::class, function (BookingCancelledClient $notification, array $channels) use ($client, $booking) {
            $data = $notification->toDatabase($client);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $data['type'] === 'booking_cancelled'
                && $data['booking_token'] === $booking->token
                && $data['customer_name'] === 'Ana';
        });
        Notification::assertNotSentTo($worker, BookingCancelledClient::class);
    }

    public function test_owner_is_notified_when_customer_cancels_from_fallback_page(): void
    {
        Notification::fake();
        [, $booking] = $this->makeBooking('pending');
        $client = $booking->slot->worker->client;

        $this->post("/manage/{$booking->token}/cancel")->assertRedirect();

        Notification::assertSentTo($client, BookingCancelledClient::class);
    }
}
