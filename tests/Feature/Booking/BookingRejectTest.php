<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingRejectedCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingRejectTest extends TestCase
{
    use RefreshDatabase;

    private function setupBooking(string $status = 'pending'): array
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => false]);
        $booking = Booking::factory()->create(['slot_id' => $slot->id, 'status' => $status]);

        return [$client, $booking, $slot];
    }

    public function test_client_can_reject_pending_booking(): void
    {
        Notification::fake();

        [$client, $booking] = $this->setupBooking('pending');

        $response = $this->actingAs($client)
            ->patchJson("/api/bookings/{$booking->token}/reject");

        $response->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'rejected']);
    }

    public function test_slot_is_freed_after_reject(): void
    {
        Notification::fake();

        [$client, $booking, $slot] = $this->setupBooking('pending');

        $this->actingAs($client)
            ->patchJson("/api/bookings/{$booking->token}/reject");

        $this->assertDatabaseHas('slots', ['id' => $slot->id, 'is_available' => true]);
    }

    public function test_customer_receives_rejected_notification(): void
    {
        Notification::fake();

        [$client, $booking] = $this->setupBooking('pending');

        $this->actingAs($client)
            ->patchJson("/api/bookings/{$booking->token}/reject");

        Notification::assertSentTo($booking, BookingRejectedCustomer::class);
    }

    public function test_no_notification_when_customer_has_no_email(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => false]);
        $booking = Booking::factory()->withoutEmail()->create(['slot_id' => $slot->id, 'status' => 'pending']);

        $this->actingAs($client)
            ->patchJson("/api/bookings/{$booking->token}/reject");

        Notification::assertNotSentTo($booking, BookingRejectedCustomer::class);
    }

    public function test_returns_403_when_client_does_not_own_booking(): void
    {
        Notification::fake();

        [, $booking] = $this->setupBooking('pending');
        $otherClient = User::factory()->client()->create();

        $response = $this->actingAs($otherClient)
            ->patchJson("/api/bookings/{$booking->token}/reject");

        $response->assertStatus(403);
    }

    public function test_returns_422_when_booking_is_not_pending(): void
    {
        Notification::fake();

        [$client, $booking] = $this->setupBooking('confirmed');

        $response = $this->actingAs($client)
            ->patchJson("/api/bookings/{$booking->token}/reject");

        $response->assertStatus(422);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        [, $booking] = $this->setupBooking('pending');

        $response = $this->patchJson("/api/bookings/{$booking->token}/reject");

        $response->assertStatus(401);
    }
}
