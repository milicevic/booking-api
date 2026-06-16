<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingPendingClient;
use App\Notifications\BookingPendingCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingStoreTest extends TestCase
{
    use RefreshDatabase;

    private function makeSlotForClient(User $client): Slot
    {
        $worker = User::factory()->worker($client->id)->create();

        return Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => true]);
    }

    // --- Ručna potvrda (auto_confirm = false) ---

    public function test_booking_is_created_with_pending_status_when_manual_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => false]);
        $slot = $this->makeSlotForClient($client);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', ['slot_id' => $slot->id, 'status' => 'pending']);
    }

    public function test_slot_becomes_unavailable_after_booking(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $slot = $this->makeSlotForClient($client);

        $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $this->assertDatabaseHas('slots', ['id' => $slot->id, 'is_available' => false]);
    }

    public function test_customer_receives_pending_notification_when_manual_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => false]);
        $slot = $this->makeSlotForClient($client);

        $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $booking = Booking::where('slot_id', $slot->id)->firstOrFail();
        Notification::assertSentTo($booking, BookingPendingCustomer::class);
        Notification::assertNotSentTo($client, BookingConfirmedClient::class);
    }

    public function test_client_receives_pending_notification_when_manual_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => false]);
        $slot = $this->makeSlotForClient($client);

        $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        Notification::assertSentTo($client, BookingPendingClient::class);
    }

    public function test_no_email_sent_to_customer_without_email_when_manual_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => false]);
        $slot = $this->makeSlotForClient($client);

        $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
        ]);

        $booking = Booking::where('slot_id', $slot->id)->firstOrFail();
        Notification::assertNotSentTo($booking, BookingPendingCustomer::class);
        Notification::assertSentTo($client, BookingPendingClient::class);
    }

    // --- Auto potvrda (auto_confirm = true) ---

    public function test_booking_is_confirmed_immediately_when_auto_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => true]);
        $slot = $this->makeSlotForClient($client);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', ['slot_id' => $slot->id, 'status' => 'confirmed']);
    }

    public function test_customer_receives_confirmed_notification_when_auto_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => true]);
        $slot = $this->makeSlotForClient($client);

        $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $booking = Booking::where('slot_id', $slot->id)->firstOrFail();
        Notification::assertSentTo($booking, BookingConfirmedCustomer::class);
        Notification::assertNotSentTo($booking, BookingPendingCustomer::class);
    }

    public function test_client_receives_confirmed_notification_when_auto_confirm(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => true]);
        $slot = $this->makeSlotForClient($client);

        $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        Notification::assertSentTo($client, BookingConfirmedClient::class);
        Notification::assertNotSentTo($client, BookingPendingClient::class);
    }

    // --- Greške ---

    public function test_returns_409_when_slot_is_not_available(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $slot = $this->makeSlotForClient($client);
        $slot->update(['is_available' => false]);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $response->assertStatus(409);
    }

    public function test_returns_422_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/bookings', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['slot_id', 'customer_name']);
    }

    // --- Service ---

    public function test_booking_can_be_created_with_service(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'tenant_id' => $this->tenant->id, 'is_available' => true]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $worker->services()->attach($service->id);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'service_id' => $service->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', ['slot_id' => $slot->id, 'service_id' => $service->id]);
    }

    public function test_returns_422_when_service_not_assigned_to_slot_worker(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'tenant_id' => $this->tenant->id, 'is_available' => true]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        // service NOT assigned to worker

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'service_id' => $service->id,
            'customer_name' => 'Ana Anić',
        ]);

        $response->assertStatus(422);
    }

    public function test_booking_without_service_is_allowed(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $slot = $this->makeSlotForClient($client);

        $response = $this->postJson('/api/bookings', [
            'slot_id' => $slot->id,
            'customer_name' => 'Ana Anić',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', ['slot_id' => $slot->id, 'service_id' => null]);
    }
}
