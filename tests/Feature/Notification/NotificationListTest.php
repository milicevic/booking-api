<?php

namespace Tests\Feature\Notification;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingPendingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationListTest extends TestCase
{
    use RefreshDatabase;

    private function setupClientWithBooking(): array
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => false]);
        $booking = Booking::factory()->create(['slot_id' => $slot->id, 'status' => 'pending']);

        return [$client, $booking];
    }

    public function test_client_can_list_notifications(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));

        $response = $this->actingAs($client)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(1);
    }

    public function test_notifications_include_expected_data_fields(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));

        $response = $this->actingAs($client)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'booking_pending',
            'booking_token' => $booking->token,
            'customer_name' => $booking->customer_name,
        ]);
    }

    public function test_client_only_sees_own_notifications(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();
        [$otherClient] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));

        $response = $this->actingAs($otherClient)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_notifications_are_unread_by_default(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));

        $response = $this->actingAs($client)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonFragment(['read_at' => null]);
    }

    public function test_client_can_mark_notification_as_read(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));
        $notification = $client->notifications()->first();

        $response = $this->actingAs($client)->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($client->notifications()->find($notification->id)->read_at);
    }

    public function test_client_can_mark_all_notifications_as_read(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));
        $client->notify(new BookingConfirmedClient($booking));

        $response = $this->actingAs($client)->patchJson('/api/notifications/read-all');

        $response->assertOk();
        $this->assertEquals(0, $client->unreadNotifications()->count());
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();
        [$otherClient] = $this->setupClientWithBooking();

        $client->notify(new BookingPendingClient($booking));
        $notification = $client->notifications()->first();

        $response = $this->actingAs($otherClient)->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_list_notifications(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_mark_notification_as_read(): void
    {
        $response = $this->patchJson('/api/notifications/some-id/read');

        $response->assertUnauthorized();
    }

    public function test_confirmed_client_notification_is_stored(): void
    {
        [$client, $booking] = $this->setupClientWithBooking();

        $client->notify(new BookingConfirmedClient($booking));

        $response = $this->actingAs($client)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonFragment(['type' => 'booking_confirmed']);
    }
}
