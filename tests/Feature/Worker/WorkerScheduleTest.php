<?php

namespace Tests\Feature\Worker;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkerSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WorkerScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Ponedeljak, pre radnog vremena; prozor od 4 nedelje je 7.12–3.1.
        $this->travelTo(Carbon::parse('2026-12-07 07:00', 'Europe/Belgrade'));
        $this->client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($this->client->id)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'weekdays' => [1, 3],
            'start' => '09:00',
            'end' => '11:00',
            'slot_duration' => 60,
            'breaks' => [],
            ...$overrides,
        ];
    }

    private function save(User $as, array $payload = []): TestResponse
    {
        return $this->actingAs($as)->putJson("/api/workers/{$this->worker->id}/schedule", $payload ?: $this->payload());
    }

    public function test_saving_schedule_creates_slots_for_four_weeks(): void
    {
        // 4 nedelje × 2 dana × 2 termina
        $this->save($this->client)
            ->assertOk()
            ->assertJsonPath('weekdays', [1, 3])
            ->assertJsonPath('start', '09:00')
            ->assertJsonPath('weeks_ahead', 4)
            ->assertJsonPath('generated_until', '2027-01-03')
            ->assertJsonPath('generated', ['created' => 16, 'skipped' => 0, 'removed' => 0]);

        $this->assertSame(16, Slot::whereNotNull('worker_schedule_id')->count());
        $this->assertFalse(Slot::whereDate('date', '>', '2027-01-03')->exists());
    }

    public function test_schedule_is_returned_and_can_be_empty(): void
    {
        $this->actingAs($this->client)->getJson("/api/workers/{$this->worker->id}/schedule")->assertOk()->assertExactJson([]);

        $this->save($this->client);

        $this->actingAs($this->client)->getJson("/api/workers/{$this->worker->id}/schedule")
            ->assertJsonPath('slot_duration', 60)
            ->assertJsonPath('breaks', []);
    }

    public function test_changing_schedule_replaces_free_generated_slots_but_keeps_booked_blocked_and_manual(): void
    {
        $this->save($this->client);

        $booked = Slot::whereDate('date', '2026-12-09')->where('start_time', '09:00:00')->firstOrFail();
        Booking::factory()->confirmed()->create(['slot_id' => $booked->id, 'start_time' => '09:00:00', 'end_time' => '10:00:00']);
        $blocked = Slot::whereDate('date', '2026-12-14')->where('start_time', '10:00:00')->firstOrFail();
        $blocked->update(['is_blocked' => true]);
        $manual = Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => '2026-12-08', 'start_time' => '15:00:00', 'end_time' => '16:00:00']);

        // Samo sredom, 09:00–10:00
        $this->save($this->client, $this->payload(['weekdays' => [3], 'end' => '10:00']))
            ->assertJsonPath('generated', ['created' => 3, 'skipped' => 1, 'removed' => 14]);

        $this->assertModelExists($booked);
        $this->assertModelExists($blocked);
        $this->assertModelExists($manual);
        $this->assertSame(0, Slot::whereDate('date', '2026-12-07')->count());
    }

    public function test_extend_command_adds_only_new_days_and_respects_deleted_slots(): void
    {
        $this->save($this->client);
        $deleted = Slot::whereDate('date', '2026-12-14')->firstOrFail();
        $deleted->delete();

        // Nedelju dana kasnije: prozor se pomera do 10.1.
        $this->travelTo(Carbon::parse('2026-12-14 02:00', 'Europe/Belgrade'));
        app()->forgetInstance('current_tenant');

        $this->artisan('slots:extend-schedules')->expectsOutputToContain('created 4 slot(s)')->assertSuccessful();

        $this->assertModelMissing($deleted);
        $this->assertSame(2, Slot::withoutGlobalScopes()->whereDate('date', '2027-01-04')->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame('2027-01-10', WorkerSchedule::withoutGlobalScopes()->firstOrFail()->generated_until?->toDateString());

        // Drugo pokretanje istog dana ne pravi ništa
        $this->artisan('slots:extend-schedules')->expectsOutputToContain('created 0 slot(s)');
    }

    public function test_extend_command_skips_expired_tenants_and_suspended_workers(): void
    {
        $this->save($this->client);
        $this->travelTo(Carbon::parse('2026-12-21 02:00', 'Europe/Belgrade'));
        app()->forgetInstance('current_tenant');

        $this->worker->update(['is_suspended' => true]);
        $this->artisan('slots:extend-schedules')->expectsOutputToContain('Extended 0 schedule(s)');

        $this->worker->update(['is_suspended' => false]);
        $this->tenant->update(['subscription_status' => 'expired']);
        $this->artisan('slots:extend-schedules')->expectsOutputToContain('Extended 0 schedule(s)');
    }

    public function test_deleting_schedule_keeps_existing_slots(): void
    {
        $this->save($this->client);

        $this->actingAs($this->client)->deleteJson("/api/workers/{$this->worker->id}/schedule")->assertNoContent();

        $this->assertDatabaseCount('worker_schedules', 0);
        $this->assertSame(16, Slot::count());
    }

    public function test_worker_edits_own_schedule_only_with_can_edit_slots(): void
    {
        $this->save($this->worker)->assertForbidden();

        $this->worker->update(['can_edit_slots' => true]);
        $this->save($this->worker)->assertOk();

        $colleague = User::factory()->worker($this->client->id)->create(['can_edit_slots' => true]);
        $this->actingAs($colleague)->getJson("/api/workers/{$this->worker->id}/schedule")->assertForbidden();
    }

    public function test_other_business_cannot_see_or_change_schedule(): void
    {
        $other = User::factory()->client()->create();

        $this->save($other)->assertForbidden();
        $this->actingAs($other)->getJson("/api/workers/{$this->worker->id}/schedule")->assertForbidden();
    }

    public function test_schedules_of_other_tenants_are_not_visible(): void
    {
        $otherTenant = Tenant::factory()->active()->create();
        WorkerSchedule::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'worker_id' => $this->worker->id,
            'weekdays' => [1],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'slot_duration' => 60,
            'breaks' => [],
        ]);

        $this->actingAs($this->client)->getJson("/api/workers/{$this->worker->id}/schedule")->assertExactJson([]);
    }

    public function test_validation(): void
    {
        $this->save($this->client, ['weekdays' => [], 'start' => '10:00', 'end' => '09:00', 'slot_duration' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['weekdays', 'end', 'slot_duration']);
    }
}
