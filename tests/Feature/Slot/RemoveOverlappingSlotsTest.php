<?php

namespace Tests\Feature\Slot;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveOverlappingSlotsTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $this->worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $this->date = today()->addDay()->toDateString();
    }

    private function slot(string $start, string $end, ?User $worker = null): Slot
    {
        return Slot::factory()->create([
            'worker_id' => ($worker ?? $this->worker)->id,
            'date' => $this->date,
            'start_time' => "{$start}:00",
            'end_time' => "{$end}:00",
        ]);
    }

    public function test_dry_run_only_reports(): void
    {
        $this->slot('09:00', '10:00');
        $this->slot('09:00', '10:00');

        $this->artisan('slots:remove-overlaps')->expectsOutputToContain('1 overlapping slot(s) would be deleted')->assertSuccessful();

        $this->assertSame(2, Slot::count());
    }

    public function test_force_keeps_the_oldest_and_removes_duplicates(): void
    {
        $original = $this->slot('09:00', '10:00');
        $this->slot('09:00', '10:00');
        $this->slot('09:30', '10:30');
        $adjacent = $this->slot('10:30', '11:00');

        $this->artisan('slots:remove-overlaps', ['--force' => true])->expectsOutputToContain('Deleted 2 overlapping slot(s).')->assertSuccessful();

        $this->assertEqualsCanonicalizing([$original->id, $adjacent->id], Slot::pluck('id')->all());
    }

    public function test_slots_with_bookings_are_never_deleted(): void
    {
        $this->slot('09:00', '10:00');
        $booked = $this->slot('09:00', '10:00');
        Booking::factory()->create(['slot_id' => $booked->id, 'status' => 'cancelled']);

        $this->artisan('slots:remove-overlaps', ['--force' => true])->assertSuccessful();

        $this->assertSame([$booked->id], Slot::pluck('id')->all());
    }

    public function test_other_workers_are_not_touched(): void
    {
        $colleague = User::factory()->worker($this->worker->client_id)->create(['tenant_id' => $this->tenant->id]);
        $this->slot('09:00', '10:00');
        $this->slot('09:00', '10:00', $colleague);

        $this->artisan('slots:remove-overlaps', ['--force' => true])->expectsOutputToContain('No overlapping slots found.');

        $this->assertSame(2, Slot::count());
    }
}
