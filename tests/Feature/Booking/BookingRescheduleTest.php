<?php

namespace Tests\Feature\Booking;

use App\Enums\ConfirmationMode;
use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\BookingRescheduledCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BookingRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private Service $service;

    /** Termin 09:00–12:00 sutra. */
    private Slot $slot;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
        Notification::fake();

        $this->client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($this->client->id)->create();
        $this->service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 60]);
        $this->worker->services()->attach($this->service->id);

        $this->slot = $this->slotFor($this->worker, '2026-12-02', '09:00', '12:00');
        $this->booking = Booking::factory()->confirmed()->create([
            'slot_id' => $this->slot->id,
            'service_id' => $this->service->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
    }

    private function slotFor(User $worker, string $date, string $start, string $end): Slot
    {
        return Slot::factory()->create(['worker_id' => $worker->id, 'date' => $date, 'start_time' => "{$start}:00", 'end_time' => "{$end}:00"]);
    }

    private function reschedule(User $user, Slot $target, string $start): TestResponse
    {
        return $this->actingAs($user)->patchJson("/api/bookings/{$this->booking->token}/reschedule", [
            'slot_id' => $target->id,
            'start_time' => $start,
        ]);
    }

    public function test_owner_moves_booking_to_another_day_and_customer_is_notified(): void
    {
        $target = $this->slotFor($this->worker, '2026-12-03', '14:00', '16:00');

        $this->reschedule($this->client, $target, '15:00')
            ->assertOk()
            ->assertJsonPath('slot_id', $target->id)
            ->assertJsonPath('start_time', '15:00:00')
            ->assertJsonPath('end_time', '16:00:00')
            ->assertJsonPath('status', 'confirmed')
            ->assertJsonPath('slot.worker.id', $this->worker->id)
            ->assertJsonPath('service.id', $this->service->id);

        Notification::assertSentTo($this->booking, BookingRescheduledCustomer::class);
        $this->assertDatabaseHas('slots', ['id' => $target->id, 'is_available' => true]);
    }

    public function test_booking_can_move_within_its_own_time(): void
    {
        // 09:00–10:00 → 09:30–10:30 preklapa staro vreme iste rezervacije, što je dozvoljeno
        $this->reschedule($this->client, $this->slot, '09:30')
            ->assertOk()
            ->assertJsonPath('start_time', '09:30:00');
    }

    public function test_time_taken_by_another_booking_is_rejected(): void
    {
        Booking::factory()->confirmed()->create([
            'slot_id' => $this->slot->id,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->reschedule($this->client, $this->slot, '10:30')->assertConflict();
        $this->assertSame('09:00:00', $this->booking->fresh()->start_time);
    }

    public function test_blocked_slot_is_not_a_valid_target(): void
    {
        $target = $this->slotFor($this->worker, '2026-12-03', '14:00', '16:00');
        $target->update(['is_blocked' => true]);

        $this->reschedule($this->client, $target, '14:00')->assertConflict();
    }

    public function test_time_in_the_past_is_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-12-02 10:15', 'Europe/Belgrade'));

        $this->reschedule($this->client, $this->slot, '10:00')->assertConflict();
        $this->reschedule($this->client, $this->slot, '10:30')->assertOk();
    }

    public function test_cancelled_booking_cannot_be_moved(): void
    {
        $this->booking->update(['status' => 'cancelled']);

        $this->reschedule($this->client, $this->slot, '10:00')->assertUnprocessable();
    }

    public function test_target_worker_must_perform_the_service(): void
    {
        $colleague = User::factory()->worker($this->client->id)->create();
        $target = $this->slotFor($colleague, '2026-12-03', '14:00', '16:00');

        $this->reschedule($this->client, $target, '14:00')->assertUnprocessable();
    }

    public function test_owner_can_move_to_colleague_who_performs_the_service(): void
    {
        $colleague = User::factory()->worker($this->client->id)->create();
        $colleague->services()->attach($this->service->id);
        $target = $this->slotFor($colleague, '2026-12-03', '14:00', '16:00');

        $this->reschedule($this->client, $target, '14:00')->assertOk()->assertJsonPath('slot.worker.id', $colleague->id);
    }

    public function test_worker_can_move_own_booking_only_in_worker_mode(): void
    {
        $target = $this->slotFor($this->worker, '2026-12-03', '14:00', '16:00');

        $this->reschedule($this->worker, $target, '14:00')->assertForbidden();

        ClientProfile::factory()->create(['user_id' => $this->client->id, 'confirmation_mode' => ConfirmationMode::Worker]);

        $this->reschedule($this->worker, $target, '14:00')->assertOk();
    }

    public function test_worker_cannot_move_booking_to_colleagues_slot(): void
    {
        ClientProfile::factory()->create(['user_id' => $this->client->id, 'confirmation_mode' => ConfirmationMode::Worker]);
        $colleague = User::factory()->worker($this->client->id)->create();
        $colleague->services()->attach($this->service->id);
        $target = $this->slotFor($colleague, '2026-12-03', '14:00', '16:00');

        $this->reschedule($this->worker, $target, '14:00')->assertForbidden();
    }

    public function test_owner_of_another_business_cannot_move_booking(): void
    {
        $other = User::factory()->client()->create();
        $target = $this->slotFor($this->worker, '2026-12-03', '14:00', '16:00');

        $this->reschedule($other, $target, '14:00')->assertForbidden();
    }

    public function test_slot_from_another_tenant_is_not_found(): void
    {
        $otherTenant = Tenant::factory()->create();
        $foreignSlot = Slot::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'worker_id' => $this->worker->id,
            'date' => '2026-12-03',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
        ]);

        $this->withHeader('X-Tenant-Subdomain', 'localhost');
        $this->reschedule($this->client, $foreignSlot, '14:00')->assertNotFound();
    }

    public function test_validation(): void
    {
        $this->actingAs($this->client)
            ->patchJson("/api/bookings/{$this->booking->token}/reschedule", ['start_time' => '9'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slot_id', 'start_time']);
    }
}
