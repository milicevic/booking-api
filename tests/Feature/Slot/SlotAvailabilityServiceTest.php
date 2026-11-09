<?php

namespace Tests\Feature\Slot;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Services\Contracts\SlotAvailabilityServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    private string $date;

    private SlotAvailabilityServiceInterface $availability;

    protected function setUp(): void
    {
        parent::setUp();

        $client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($client->id)->create();
        $this->date = today()->addDay()->toDateString();
        $this->availability = app(SlotAvailabilityServiceInterface::class);
    }

    private function slot(string $start, string $end): Slot
    {
        return Slot::factory()->create([
            'worker_id' => $this->worker->id,
            'date' => $this->date,
            'start_time' => "{$start}:00",
            'end_time' => "{$end}:00",
        ]);
    }

    /**
     * @return list<string>
     */
    private function startTimes(int $duration): array
    {
        return array_column($this->availability->bookableTimes($this->worker->id, $duration, $this->date), 'start_time');
    }

    public function test_consecutive_slots_merge_so_a_long_service_fits_across_them(): void
    {
        $this->slot('08:00', '08:30');
        $this->slot('08:30', '09:00');

        $this->assertSame(['08:00:00'], $this->startTimes(60));
    }

    public function test_service_that_does_not_fit_offers_no_times(): void
    {
        $this->slot('08:00', '08:30');

        $this->assertSame([], $this->startTimes(45));
    }

    public function test_end_of_active_booking_is_offered_as_a_start_time(): void
    {
        $slot = $this->slot('08:00', '09:00');
        Booking::factory()->create(['slot_id' => $slot->id, 'start_time' => '08:00:00', 'end_time' => '08:40:00']);

        $this->assertSame(['08:40:00'], $this->startTimes(20));
    }

    public function test_cancelled_booking_does_not_take_time(): void
    {
        $slot = $this->slot('08:00', '09:00');
        Booking::factory()->create(['slot_id' => $slot->id, 'status' => 'cancelled']);

        $this->assertSame(['08:00:00'], $this->startTimes(60));
    }

    public function test_refresh_availability_marks_fully_booked_slot_unavailable(): void
    {
        $slot = $this->slot('08:00', '09:00');
        $partly = $this->slot('09:00', '10:00');
        Booking::factory()->create(['slot_id' => $slot->id]);
        Booking::factory()->create(['slot_id' => $partly->id, 'start_time' => '09:00:00', 'end_time' => '09:30:00']);

        $this->availability->refreshAvailability($this->worker->id, $this->date);

        $this->assertFalse($slot->fresh()?->is_available);
        $this->assertTrue($partly->fresh()?->is_available);
    }
}
