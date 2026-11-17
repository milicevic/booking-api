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
use App\Notifications\BookingPendingCustomer;
use App\Notifications\BookingRescheduledCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CustomerRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private Service $service;

    private Booking $booking;

    /** Termin 14:00–17:00 drugog dana. */
    private Slot $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
        Notification::fake();

        $this->client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($this->client->id)->create(['email' => 'radnik@example.com']);
        $this->service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 60]);
        $this->worker->services()->attach($this->service->id);

        $slot = Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => '2026-12-02', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $this->target = Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => '2026-12-03', 'start_time' => '14:00:00', 'end_time' => '17:00:00']);
        $this->booking = Booking::factory()->confirmed()->create([
            'slot_id' => $slot->id,
            'service_id' => $this->service->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
    }

    private function profile(array $attributes): void
    {
        ClientProfile::factory()->create(['user_id' => $this->client->id, ...$attributes]);
    }

    private function move(string $start = '14:00', ?Slot $target = null): TestResponse
    {
        return $this->patchJson("/api/bookings/{$this->booking->token}/customer-reschedule", [
            'slot_id' => ($target ?? $this->target)->id,
            'start_time' => $start,
        ]);
    }

    public function test_moved_booking_waits_for_confirmation_again_in_owner_mode(): void
    {
        $this->profile(['confirmation_mode' => ConfirmationMode::Owner]);

        $this->move()
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('slot_id', $this->target->id)
            ->assertJsonPath('start_time', '14:00:00')
            ->assertJsonMissingPath('slot.worker.email');

        Notification::assertSentTo($this->booking, BookingPendingCustomer::class);
        Notification::assertSentTo($this->client, BookingPendingClient::class);
    }

    public function test_worker_mode_sends_the_new_request_to_worker(): void
    {
        $this->profile(['confirmation_mode' => ConfirmationMode::Worker]);

        $this->move()->assertJsonPath('status', 'pending');

        Notification::assertSentTo($this->worker, BookingPendingClient::class);
        Notification::assertNotSentTo($this->client, BookingPendingClient::class);
    }

    public function test_auto_mode_keeps_booking_confirmed(): void
    {
        $this->profile(['confirmation_mode' => ConfirmationMode::Auto]);

        $this->move()->assertJsonPath('status', 'confirmed');

        Notification::assertSentTo($this->booking, BookingRescheduledCustomer::class);
        Notification::assertSentTo($this->client, BookingConfirmedClient::class);
    }

    public function test_owner_mail_says_the_booking_was_moved(): void
    {
        $this->profile(['confirmation_mode' => ConfirmationMode::Owner]);

        $this->move();

        Notification::assertSentTo($this->client, BookingPendingClient::class, function (BookingPendingClient $notification) {
            return $notification->toMail($this->client)->subject === __('notifications.booking_pending_client.subject_rescheduled', ['name' => $this->booking->customer_name]);
        });
    }

    public function test_only_offered_times_are_accepted(): void
    {
        // 14:30 nije početak termina niti kraj rezervacije, pa se kupcu ne nudi
        $this->move('14:30')->assertConflict();
        $this->assertSame('09:00:00', $this->booking->fresh()->start_time);
    }

    public function test_moving_is_not_allowed_within_default_cutoff_of_six_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-12-02 03:01', 'Europe/Belgrade'));

        $this->move()->assertUnprocessable()->assertJsonPath('message', __('messages.reschedule_too_late', ['hours' => 6]));
    }

    public function test_cutoff_is_configurable_by_owner(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['reschedule_cutoff_hours' => 48])
            ->assertJsonPath('client_profile.reschedule_cutoff_hours', 48);

        // Do početka (2.12. u 09:00) ostaje 25 h, manje od 48
        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
        $this->app['auth']->forgetGuards();
        $this->move()->assertUnprocessable();

        $this->client->clientProfile()->update(['reschedule_cutoff_hours' => 0]);
        $this->move()->assertOk();
    }

    public function test_public_booking_tells_whether_customer_can_reschedule(): void
    {
        $this->getJson("/api/bookings/{$this->booking->token}")
            ->assertJsonPath('can_reschedule', true)
            ->assertJsonPath('reschedule_cutoff_hours', 6);

        $this->travelTo(Carbon::parse('2026-12-02 04:00', 'Europe/Belgrade'));
        $this->getJson("/api/bookings/{$this->booking->token}")->assertJsonPath('can_reschedule', false);
    }

    public function test_tenant_config_exposes_cutoff(): void
    {
        $this->profile(['reschedule_cutoff_hours' => 12]);

        $this->withHeader('X-Tenant-Subdomain', 'localhost')->getJson('/api/tenant/config')
            ->assertJsonPath('reschedule_cutoff_hours', 12);
    }

    public function test_cancelled_booking_cannot_be_moved(): void
    {
        $this->booking->update(['status' => 'cancelled']);

        $this->move()->assertNotFound();
    }

    public function test_worker_must_perform_the_service(): void
    {
        $colleague = User::factory()->worker($this->client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $colleague->id, 'date' => '2026-12-03', 'start_time' => '14:00:00', 'end_time' => '17:00:00']);

        $this->move('14:00', $slot)->assertUnprocessable();
    }

    public function test_cutoff_validation(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['reschedule_cutoff_hours' => -1])
            ->assertJsonValidationErrors('reschedule_cutoff_hours');
    }
}
