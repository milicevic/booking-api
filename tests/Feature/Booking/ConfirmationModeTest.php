<?php

namespace Tests\Feature\Booking;

use App\Enums\ConfirmationMode;
use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingPendingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConfirmationModeTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private Slot $slot;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($this->client->id)->create(['email' => 'radnik@example.com']);
        $this->slot = Slot::factory()->create(['worker_id' => $this->worker->id, 'start_time' => '10:00:00', 'end_time' => '11:00:00']);
        $this->service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 60]);
        $this->worker->services()->attach($this->service->id);
    }

    private function mode(ConfirmationMode $mode): void
    {
        ClientProfile::factory()->create(['user_id' => $this->client->id, 'confirmation_mode' => $mode]);
    }

    private function book(): Booking
    {
        $this->postJson('/api/bookings', [
            'slot_id' => $this->slot->id,
            'service_id' => $this->service->id,
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
        ])->assertCreated();

        return Booking::firstOrFail();
    }

    // --- Podešavanje ---

    public function test_settings_accept_confirmation_mode_and_keep_legacy_flag_in_sync(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['confirmation_mode' => 'auto'])
            ->assertOk()
            ->assertJsonPath('client_profile.confirmation_mode', 'auto')
            ->assertJsonPath('client_profile.auto_confirm_bookings', true);

        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['confirmation_mode' => 'worker'])
            ->assertJsonPath('client_profile.confirmation_mode', 'worker')
            ->assertJsonPath('client_profile.auto_confirm_bookings', false);
    }

    public function test_legacy_auto_confirm_flag_still_sets_mode(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => true])
            ->assertJsonPath('client_profile.confirmation_mode', 'auto');

        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => false])
            ->assertJsonPath('client_profile.confirmation_mode', 'owner');
    }

    public function test_legacy_flag_false_keeps_worker_mode(): void
    {
        $this->mode(ConfirmationMode::Worker);

        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => false])
            ->assertJsonPath('client_profile.confirmation_mode', 'worker');
    }

    public function test_invalid_mode_is_rejected(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['confirmation_mode' => 'nobody'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation_mode');
    }

    public function test_onboarding_completed_flag_sets_and_clears_timestamp(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['onboarding_completed' => true])
            ->assertJsonPath('client_profile.onboarding_completed_at', fn ($value) => $value !== null);

        $this->actingAs($this->client)->getJson('/api/auth/me')
            ->assertJsonPath('client_profile.confirmation_mode', 'owner')
            ->assertJsonPath('client_profile.onboarding_completed_at', fn ($value) => $value !== null);

        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['onboarding_completed' => false])
            ->assertJsonPath('client_profile.onboarding_completed_at', null);
    }

    // --- Nova rezervacija ---

    public function test_auto_mode_confirms_booking_immediately(): void
    {
        Notification::fake();
        $this->mode(ConfirmationMode::Auto);

        $this->assertSame('confirmed', $this->book()->status->value);
        Notification::assertSentTo($this->client, BookingConfirmedClient::class);
    }

    public function test_owner_mode_sends_request_to_owner(): void
    {
        Notification::fake();
        $this->mode(ConfirmationMode::Owner);

        $this->assertSame('pending', $this->book()->status->value);
        Notification::assertSentTo($this->client, BookingPendingClient::class);
        Notification::assertNotSentTo($this->worker, BookingPendingClient::class);
    }

    public function test_worker_mode_sends_request_to_worker(): void
    {
        Notification::fake();
        $this->mode(ConfirmationMode::Worker);

        $this->assertSame('pending', $this->book()->status->value);
        Notification::assertSentTo($this->worker, BookingPendingClient::class);
        Notification::assertNotSentTo($this->client, BookingPendingClient::class);
    }

    public function test_worker_mode_falls_back_to_owner_for_worker_without_email(): void
    {
        Notification::fake();
        $this->mode(ConfirmationMode::Worker);
        $this->worker->update(['email' => null]);

        $this->book();

        Notification::assertSentTo($this->client, BookingPendingClient::class);
    }

    // --- Ko sme da potvrdi ---

    public function test_worker_can_confirm_own_booking_in_worker_mode(): void
    {
        Notification::fake();
        $this->mode(ConfirmationMode::Worker);
        $booking = Booking::factory()->pending()->create(['slot_id' => $this->slot->id]);

        $this->actingAs($this->worker)->patchJson("/api/bookings/{$booking->token}/confirm")->assertOk();
        $this->assertSame('confirmed', $booking->fresh()->status->value);
    }

    public function test_owner_can_still_confirm_in_worker_mode(): void
    {
        Notification::fake();
        $this->mode(ConfirmationMode::Worker);
        $booking = Booking::factory()->pending()->create(['slot_id' => $this->slot->id]);

        $this->actingAs($this->client)->patchJson("/api/bookings/{$booking->token}/reject")->assertOk();
    }

    public function test_worker_cannot_confirm_in_owner_mode(): void
    {
        $this->mode(ConfirmationMode::Owner);
        $booking = Booking::factory()->pending()->create(['slot_id' => $this->slot->id]);

        $this->actingAs($this->worker)->patchJson("/api/bookings/{$booking->token}/confirm")->assertForbidden();
    }

    public function test_worker_cannot_confirm_colleagues_booking(): void
    {
        $this->mode(ConfirmationMode::Worker);
        $colleague = User::factory()->worker($this->client->id)->create();
        $booking = Booking::factory()->pending()->create([
            'slot_id' => Slot::factory()->create(['worker_id' => $colleague->id])->id,
        ]);

        $this->actingAs($this->worker)->patchJson("/api/bookings/{$booking->token}/confirm")->assertForbidden();
    }
}
