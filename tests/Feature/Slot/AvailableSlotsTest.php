<?php

namespace Tests\Feature\Slot;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailableSlotsTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-27 12:30:00'));

        $client = User::factory()->client()->create();
        $this->worker = User::factory()->worker($client->id)->create();
    }

    private function makeSlot(string $date, string $startTime, bool $isAvailable = true): Slot
    {
        return Slot::factory()->create([
            'worker_id' => $this->worker->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => Carbon::parse($startTime)->addHour()->format('H:i:s'),
            'is_available' => $isAvailable,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function availableSlotIds(array $query = []): array
    {
        return collect(
            $this->getJson('/api/slots/available?'.http_build_query($query))->assertOk()->json()
        )->pluck('id')->all();
    }

    public function test_returns_future_slots(): void
    {
        $tomorrow = $this->makeSlot('2026-10-28', '09:00:00');
        $nextMonth = $this->makeSlot('2026-11-27', '09:00:00');

        $this->assertSame([$tomorrow->id, $nextMonth->id], $this->availableSlotIds());
    }

    public function test_does_not_return_slots_from_past_days(): void
    {
        $this->makeSlot('2026-10-26', '15:00:00');
        $this->makeSlot('2026-08-13', '09:00:00');

        $this->assertSame([], $this->availableSlotIds());
    }

    public function test_returns_todays_slots_that_have_not_started_yet(): void
    {
        $this->makeSlot('2026-10-27', '09:00:00');
        $this->makeSlot('2026-10-27', '12:00:00');
        $this->makeSlot('2026-10-27', '12:30:00');
        $later = $this->makeSlot('2026-10-27', '13:00:00');

        $this->assertSame([$later->id], $this->availableSlotIds());
    }

    public function test_does_not_return_unavailable_future_slots(): void
    {
        $this->makeSlot('2026-10-28', '09:00:00', isAvailable: false);

        $this->assertSame([], $this->availableSlotIds());
    }

    public function test_date_filter_for_a_past_day_returns_nothing(): void
    {
        $this->makeSlot('2026-10-26', '15:00:00');

        $this->assertSame([], $this->availableSlotIds(['date' => '2026-10-26']));
    }

    public function test_date_filter_still_works_for_future_days(): void
    {
        $this->makeSlot('2026-10-28', '09:00:00');
        $dayAfter = $this->makeSlot('2026-10-29', '09:00:00');

        $this->assertSame([$dayAfter->id], $this->availableSlotIds(['date' => '2026-10-29']));
    }
}
