<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
