<?php

namespace Tests\Feature\Slot;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SlotStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $worker;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $this->worker = User::factory()->worker($this->client->id)->create(['tenant_id' => $this->tenant->id]);
        $this->date = today()->addDay()->toDateString();

        Slot::factory()->create(['worker_id' => $this->worker->id, 'date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00']);
    }

    private function store(string $start, string $end, ?User $worker = null, ?string $date = null): TestResponse
    {
        return $this->actingAs($this->client)->postJson('/api/slots', [
            'worker_id' => ($worker ?? $this->worker)->id,
            'date' => $date ?? $this->date,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function test_the_same_slot_cannot_be_created_twice(): void
    {
        $this->withHeader('Accept-Language', 'sr')
            ->store('09:00', '10:00')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Radnik već ima termin koji se preklapa sa ovim vremenom.');

        $this->assertSame(1, Slot::where('worker_id', $this->worker->id)->count());
    }

    public function test_partly_overlapping_slots_are_rejected(): void
    {
        $this->store('09:30', '10:30')->assertUnprocessable();
        $this->store('08:30', '09:15')->assertUnprocessable();
        $this->store('09:15', '09:45')->assertUnprocessable();
        $this->store('08:00', '11:00')->assertUnprocessable();
    }

    public function test_adjacent_slots_are_allowed(): void
    {
        $this->store('08:00', '09:00')->assertCreated();
        $this->store('10:00', '11:00')->assertCreated();
    }

    public function test_another_worker_or_day_may_use_the_same_time(): void
    {
        $colleague = User::factory()->worker($this->client->id)->create(['tenant_id' => $this->tenant->id]);

        $this->store('09:00', '10:00', $colleague)->assertCreated();
        $this->store('09:00', '10:00', date: today()->addDays(2)->toDateString())->assertCreated();
    }
}
