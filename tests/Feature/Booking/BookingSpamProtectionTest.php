<?php

namespace Tests\Feature\Booking;

use App\Models\BlockedCustomer;
use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BookingCancelledCustomer;
use App\Notifications\BookingRejectedCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Zaštita od zauzimanja termina: limiti po kupcu, blokiranje kupca i istek rezervacija na čekanju.
 */
class BookingSpamProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private Service $service;

    private ?Slot $slot = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
        Notification::fake();

        $this->client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $this->client->id]);
        $this->worker = User::factory()->worker($this->client->id)->create();
        $this->service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 30]);
        $this->worker->services()->attach($this->service->id);
        // Ceo dan u terminima od 30 min (kupcu se nude počeci termina): dovoljno mesta za mnogo rezervacija
        foreach (range(16, 35) as $half) {
            $slot = Slot::factory()->create([
                'worker_id' => $this->worker->id,
                'date' => '2026-12-02',
                'start_time' => Slot::fromMinutes($half * 30),
                'end_time' => Slot::fromMinutes(($half + 1) * 30),
            ]);
            $this->slot ??= $slot;
        }
    }

    private function book(string $start, string $phone = '+381601234567', ?string $email = null): TestResponse
    {
        return $this->postJson('/api/bookings', array_filter([
            'slot_id' => $this->slot->id,
            'start_time' => $start,
            'service_id' => $this->service->id,
            'customer_name' => 'Marko',
            'customer_phone' => $phone,
            'customer_email' => $email,
        ]));
    }

    // --- Limiti po kupcu ---

    public function test_customer_can_have_at_most_three_upcoming_bookings(): void
    {
        $this->book('08:00')->assertCreated();
        $this->book('08:30')->assertCreated();
        $this->book('09:00')->assertCreated();

        $this->book('09:30')
            ->assertUnprocessable()
            ->assertJsonPath('message', __('messages.customer_limit_active', ['max' => 3]));

        // Drugi kupac i dalje može
        $this->book('09:30', '+381641111111')->assertCreated();
    }

    public function test_same_email_with_another_phone_counts_as_the_same_customer(): void
    {
        $this->book('08:00', '+381600000001', 'Marko@Example.com')->assertCreated();
        $this->book('08:30', '+381600000002', 'marko@example.com')->assertCreated();
        $this->book('09:00', '+381600000003', 'MARKO@example.com')->assertCreated();

        $this->book('09:30', '+381600000004', 'marko@example.com')->assertUnprocessable();
    }

    public function test_cancelled_bookings_free_the_limit_but_count_in_the_daily_limit(): void
    {
        foreach (['08:00', '08:30', '09:00', '09:30', '10:00'] as $start) {
            $this->book($start)->assertCreated();
            Booking::latest('id')->first()->update(['status' => 'cancelled']);
        }

        $this->book('10:30')
            ->assertUnprocessable()
            ->assertJsonPath('message', __('messages.customer_limit_daily'));

        $this->travel(25)->hours();
        $this->book('15:00')->assertCreated();
    }

    public function test_past_bookings_do_not_count_as_upcoming(): void
    {
        $past = Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => '2026-11-30', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        Booking::factory()->confirmed()->count(3)->create(['slot_id' => $past->id, 'customer_phone' => '+381601234567', 'created_at' => now()->subDays(3)]);

        $this->book('08:00')->assertCreated();
    }

    public function test_owner_can_change_or_turn_off_the_limits(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['max_active_bookings_per_customer' => 1, 'max_daily_bookings_per_customer' => 0])
            ->assertJsonPath('client_profile.max_active_bookings_per_customer', 1)
            ->assertJsonPath('client_profile.max_daily_bookings_per_customer', 0);
        $this->app['auth']->forgetGuards();

        $this->book('08:00')->assertCreated();
        $this->book('08:30')->assertUnprocessable();

        $this->client->clientProfile->update(['max_active_bookings_per_customer' => 0]);
        $this->book('08:30')->assertCreated();
        $this->book('09:00')->assertCreated();
    }

    public function test_limits_count_only_bookings_of_the_same_business(): void
    {
        $otherTenant = Tenant::factory()->active()->create();
        foreach (range(1, 3) as $i) {
            Booking::withoutGlobalScopes()->create([
                'tenant_id' => $otherTenant->id,
                'slot_id' => $this->slot->id,
                'customer_name' => 'Marko',
                'customer_phone' => '+381601234567',
                'start_time' => '17:00:00',
                'end_time' => '17:30:00',
                'status' => 'confirmed',
                'token' => "other-{$i}",
            ]);
        }

        $this->book('08:00')->assertCreated();
    }

    // --- Blokiranje ---

    public function test_owner_blocks_customer_and_their_upcoming_bookings_are_cancelled(): void
    {
        $this->book('08:00', '+381601234567', 'marko@example.com')->assertCreated();
        $this->book('09:00', '+381601234567', 'marko@example.com')->assertCreated();
        $booking = Booking::firstOrFail();

        $this->actingAs($this->client)
            ->postJson("/api/bookings/{$booking->token}/block-customer")
            ->assertCreated()
            ->assertJsonPath('cancelled', 2)
            ->assertJsonPath('blocked.phone', '+381601234567');

        $this->assertSame(0, Booking::whereIn('status', ['pending', 'confirmed'])->count());
        Notification::assertSentTo($booking, BookingCancelledCustomer::class);
        $this->app['auth']->forgetGuards();

        // Blokiran po telefonu, i po emailu sa drugim telefonom
        $this->book('10:00')->assertUnprocessable()->assertJsonPath('message', __('messages.booking_not_allowed'));
        $this->book('10:00', '+381659999999', 'MARKO@example.com')->assertUnprocessable();
        $this->book('10:00', '+381659999999')->assertCreated();
    }

    public function test_owner_sees_and_unblocks_customers(): void
    {
        $this->book('08:00')->assertCreated();
        $booking = Booking::firstOrFail();
        $this->actingAs($this->client)->postJson("/api/bookings/{$booking->token}/block-customer")->assertCreated();

        $blocked = $this->actingAs($this->client)->getJson('/api/blocked-customers')
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Marko')
            ->json('0.id');

        $this->actingAs($this->client)->deleteJson("/api/blocked-customers/{$blocked}")->assertOk();
        $this->app['auth']->forgetGuards();

        $this->book('09:00')->assertCreated();
    }

    public function test_worker_and_other_owner_cannot_block_or_unblock(): void
    {
        $this->book('08:00')->assertCreated();
        $booking = Booking::firstOrFail();
        $other = User::factory()->client()->create();

        $this->actingAs($this->worker)->postJson("/api/bookings/{$booking->token}/block-customer")->assertForbidden();
        $this->actingAs($other)->postJson("/api/bookings/{$booking->token}/block-customer")->assertForbidden();
        $this->actingAs($this->worker)->getJson('/api/blocked-customers')->assertForbidden();

        $blocked = BlockedCustomer::create(['phone' => '+381600000000']);
        $otherTenant = Tenant::factory()->active()->create();
        $foreign = BlockedCustomer::withoutGlobalScopes()->create(['tenant_id' => $otherTenant->id, 'phone' => '+381600000001']);

        $this->actingAs($this->worker)->deleteJson("/api/blocked-customers/{$blocked->id}")->assertForbidden();
        $this->actingAs($this->client)->deleteJson("/api/blocked-customers/{$foreign->id}")->assertNotFound();
    }

    // --- Istek rezervacija na čekanju ---

    public function test_pending_booking_expires_after_twelve_hours_and_frees_the_time(): void
    {
        $this->book('10:00')->assertCreated();
        $booking = Booking::firstOrFail();
        $this->assertSame('pending', $booking->status->value);

        $this->travel(11)->hours();
        $this->artisan('bookings:expire-pending')->expectsOutputToContain('Rejected 0');

        $this->travel(2)->hours();
        $this->artisan('bookings:expire-pending')->expectsOutputToContain('Rejected 1');

        $this->assertSame('rejected', $booking->fresh()->status->value);
        Notification::assertSentTo($booking, BookingRejectedCustomer::class);
        $this->assertTrue($this->slot->fresh()->is_available);
    }

    public function test_pending_booking_expires_when_the_time_starts_even_without_expiry_hours(): void
    {
        $this->client->clientProfile->update(['pending_expiry_hours' => 0]);
        $this->book('10:00')->assertCreated();

        $this->travelTo(Carbon::parse('2026-12-02 09:59', 'Europe/Belgrade'));
        $this->artisan('bookings:expire-pending')->expectsOutputToContain('Rejected 0');

        $this->travelTo(Carbon::parse('2026-12-02 10:00', 'Europe/Belgrade'));
        $this->artisan('bookings:expire-pending')->expectsOutputToContain('Rejected 1');
    }

    public function test_confirmed_bookings_never_expire(): void
    {
        $this->book('10:00')->assertCreated();
        Booking::firstOrFail()->update(['status' => 'confirmed']);

        $this->travel(3)->days();
        $this->artisan('bookings:expire-pending')->expectsOutputToContain('Rejected 0');
    }
}
