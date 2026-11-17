<?php

namespace Tests\Feature\Slot;

use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlotBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private Slot $slot;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
        $this->client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($this->client->id)->create();
        $this->service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 60]);
        $this->worker->services()->attach($this->service->id);
        $this->slot = Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => '2026-12-02', 'start_time' => '10:00:00', 'end_time' => '11:00:00']);
    }

    public function test_owner_blocks_slot_and_it_disappears_from_public_endpoints(): void
    {
        $this->actingAs($this->client)
            ->patchJson("/api/slots/{$this->slot->id}", ['is_blocked' => true])
            ->assertOk()
            ->assertJsonPath('is_blocked', true);

        $this->getJson('/api/slots/available')->assertJsonCount(0);
        $this->getJson("/api/slots/times?worker_id={$this->worker->id}&service_id={$this->service->id}")->assertJsonCount(0);

        $this->postJson('/api/bookings', [
            'slot_id' => $this->slot->id,
            'service_id' => $this->service->id,
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
        ])->assertConflict();
    }

    public function test_unblocked_slot_is_offered_again(): void
    {
        $this->slot->update(['is_blocked' => true]);

        $this->actingAs($this->client)->patchJson("/api/slots/{$this->slot->id}", ['is_blocked' => false])->assertOk();

        $this->getJson('/api/slots/available')->assertJsonCount(1);
        $this->getJson("/api/slots/times?worker_id={$this->worker->id}&service_id={$this->service->id}")
            ->assertJsonPath('0.start_time', '10:00:00');
    }

    public function test_blocked_slot_is_still_listed_in_admin_calendar(): void
    {
        $this->slot->update(['is_blocked' => true]);

        $this->actingAs($this->client)->getJson('/api/slots')->assertJsonPath('0.is_blocked', true);
    }

    public function test_worker_needs_can_edit_slots_to_block_own_slot(): void
    {
        $this->actingAs($this->worker)->patchJson("/api/slots/{$this->slot->id}", ['is_blocked' => true])->assertForbidden();

        $this->worker->update(['can_edit_slots' => true]);

        $this->actingAs($this->worker)->patchJson("/api/slots/{$this->slot->id}", ['is_blocked' => true])->assertOk();
    }

    public function test_worker_cannot_block_or_delete_colleagues_slot(): void
    {
        $colleague = User::factory()->worker($this->client->id)->create(['can_edit_slots' => true]);

        $this->actingAs($colleague)->patchJson("/api/slots/{$this->slot->id}", ['is_blocked' => true])->assertForbidden();
        $this->actingAs($colleague)->deleteJson("/api/slots/{$this->slot->id}")->assertForbidden();
    }

    public function test_worker_with_permission_can_delete_own_slot(): void
    {
        $this->worker->update(['can_edit_slots' => true]);

        $this->actingAs($this->worker)->deleteJson("/api/slots/{$this->slot->id}")->assertNoContent();
    }

    public function test_owner_of_another_business_cannot_block(): void
    {
        $other = User::factory()->client()->create();

        $this->actingAs($other)->patchJson("/api/slots/{$this->slot->id}", ['is_blocked' => true])->assertForbidden();
    }

    public function test_is_blocked_is_required(): void
    {
        $this->actingAs($this->client)->patchJson("/api/slots/{$this->slot->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_blocked');
    }
}
