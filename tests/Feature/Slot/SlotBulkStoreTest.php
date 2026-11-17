<?php

namespace Tests\Feature\Slot;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlotBulkStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Utorak
        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
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
            'worker_id' => $this->worker->id,
            'date_from' => '2026-12-07', // ponedeljak
            'date_to' => '2026-12-13',   // nedelja
            'weekdays' => [1, 3, 5],
            'start' => '09:00',
            'end' => '12:00',
            'slot_duration' => 30,
            'breaks' => [['start' => '10:00', 'end' => '10:30']],
            ...$overrides,
        ];
    }

    public function test_creates_slots_for_selected_weekdays_without_breaks(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/slots/bulk', $this->payload())
            ->assertCreated()
            ->assertExactJson(['created' => 15, 'skipped' => 0]);

        // 3 dana × (6 termina − 1 u pauzi)
        $this->assertSame(['2026-12-07', '2026-12-09', '2026-12-11'], Slot::orderBy('date')->get()->map(fn (Slot $s) => $s->date->toDateString())->unique()->values()->all());
        $this->assertDatabaseMissing('slots', ['start_time' => '10:00:00']);
        $this->assertTrue(Slot::whereDate('date', '2026-12-07')
            ->where(['start_time' => '11:30:00', 'end_time' => '12:00:00', 'tenant_id' => $this->tenant->id])
            ->exists());
    }

    public function test_overlapping_existing_slots_are_skipped(): void
    {
        Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => '2026-12-07', 'start_time' => '09:15:00', 'end_time' => '09:45:00']);

        $this->actingAs($this->client)
            ->postJson('/api/slots/bulk', $this->payload())
            ->assertExactJson(['created' => 13, 'skipped' => 2]);
    }

    public function test_repeating_the_same_request_creates_nothing_new(): void
    {
        $this->actingAs($this->client)->postJson('/api/slots/bulk', $this->payload());

        $this->actingAs($this->client)
            ->postJson('/api/slots/bulk', $this->payload())
            ->assertExactJson(['created' => 0, 'skipped' => 15]);
    }

    public function test_past_times_today_are_not_created(): void
    {
        $this->travelTo(Carbon::parse('2026-12-07 10:40', 'Europe/Belgrade'));

        $this->actingAs($this->client)
            ->postJson('/api/slots/bulk', $this->payload(['date_to' => '2026-12-07', 'breaks' => []]))
            ->assertExactJson(['created' => 2, 'skipped' => 0]);
    }

    public function test_too_many_slots_are_rejected(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/slots/bulk', $this->payload([
                'date_to' => '2027-12-31',
                'weekdays' => [1, 2, 3, 4, 5, 6, 7],
                'start' => '00:00',
                'end' => '23:00',
                'slot_duration' => 15,
                'breaks' => [],
            ]))
            ->assertUnprocessable();

        $this->assertDatabaseCount('slots', 0);
    }

    public function test_owner_of_another_business_is_forbidden(): void
    {
        $other = User::factory()->client()->create();

        $this->actingAs($other)->postJson('/api/slots/bulk', $this->payload())->assertForbidden();
    }

    public function test_worker_needs_can_edit_slots_and_only_for_self(): void
    {
        $this->actingAs($this->worker)->postJson('/api/slots/bulk', $this->payload())->assertForbidden();

        $this->worker->update(['can_edit_slots' => true]);
        $this->actingAs($this->worker)->postJson('/api/slots/bulk', $this->payload())->assertCreated();

        $colleague = User::factory()->worker($this->client->id)->create();
        $this->actingAs($this->worker)->postJson('/api/slots/bulk', $this->payload(['worker_id' => $colleague->id]))->assertForbidden();
    }

    public function test_validation(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/slots/bulk', $this->payload([
                'date_from' => '2026-11-30',
                'weekdays' => [0, 8],
                'end' => '08:00',
                'slot_duration' => 0,
                'breaks' => [['start' => '11:00', 'end' => '10:00']],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_from', 'weekdays.0', 'weekdays.1', 'end', 'slot_duration', 'breaks.0.end']);
    }
}
