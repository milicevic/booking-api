<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BookableTimesTest extends TestCase
{
    use RefreshDatabase;

    private User $worker;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $this->worker = User::factory()->worker($client->id)->create();
        $this->date = today()->addDay()->toDateString();

        // Radno vreme 08:00–10:00 u terminima od 30 minuta
        foreach (['08:00', '08:30', '09:00', '09:30'] as $start) {
            Slot::factory()->create([
                'worker_id' => $this->worker->id,
                'date' => $this->date,
                'start_time' => "{$start}:00",
                'end_time' => date('H:i:s', strtotime("{$start} +30 minutes")),
                'is_available' => true,
            ]);
        }
    }

    private function service(int $minutes): Service
    {
        $service = Service::factory()->create(['duration_minutes' => $minutes, 'is_active' => true]);
        $this->worker->services()->attach($service->id);

        return $service;
    }

    /**
     * @return list<string>
     */
    private function offeredStarts(?Service $service): array
    {
        return collect(
            $this->getJson('/api/slots/times?'.http_build_query(array_filter([
                'worker_id' => $this->worker->id,
                'service_id' => $service?->id,
            ])))->assertOk()->json()
        )->pluck('start_time')->all();
    }

    private function book(?Service $service, string $start): TestResponse
    {
        $slot = Slot::where('worker_id', $this->worker->id)
            ->where('start_time', '<=', "{$start}:00")
            ->where('end_time', '>', "{$start}:00")
            ->firstOrFail();

        return $this->postJson('/api/bookings', array_filter([
            'slot_id' => $slot->id,
            'start_time' => $start,
            'service_id' => $service?->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => 'ana@example.com',
        ]));
    }

    public function test_long_service_takes_exact_time_across_slots(): void
    {
        $long = $this->service(40);

        $this->book($long, '08:00')
            ->assertCreated()
            ->assertJsonPath('start_time', '08:00:00')
            ->assertJsonPath('end_time', '08:40:00');
    }

    public function test_gap_after_booking_is_offered_to_short_service(): void
    {
        $long = $this->service(40);
        $short = $this->service(20);

        $this->book($long, '08:00')->assertCreated();

        $this->assertSame(['08:40:00', '09:00:00', '09:30:00'], $this->offeredStarts($short));
        $this->book($short, '08:40')
            ->assertCreated()
            ->assertJsonPath('end_time', '09:00:00');
    }

    public function test_gap_is_not_offered_when_service_does_not_fit(): void
    {
        $long = $this->service(40);

        $this->book($long, '08:00')->assertCreated();

        // Slobodno je 08:40–10:00: 40 minuta staje od 08:40 i 09:00, a od 09:30 ostaje samo 30 minuta
        $this->assertSame(['08:40:00', '09:00:00'], $this->offeredStarts($long));
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $long = $this->service(40);
        $short = $this->service(20);

        $this->book($long, '08:00')->assertCreated();

        $this->book($short, '08:30')->assertStatus(409);
    }

    public function test_service_longer_than_working_time_is_not_offered(): void
    {
        $this->assertSame([], $this->offeredStarts($this->service(150)));
    }

    public function test_worker_without_services_books_whole_slots(): void
    {
        $this->assertSame(['08:00:00', '08:30:00', '09:00:00', '09:30:00'], $this->offeredStarts(null));

        $this->book(null, '08:00')
            ->assertCreated()
            ->assertJsonPath('end_time', '08:30:00');
    }

    public function test_cancelling_frees_the_time_again(): void
    {
        $long = $this->service(40);
        $token = $this->book($long, '08:00')->assertCreated()->json('token');

        $this->patchJson("/api/bookings/{$token}/cancel")->assertOk();

        $this->assertContains('08:00:00', $this->offeredStarts($long));
    }

    public function test_slot_is_marked_taken_only_when_fully_booked(): void
    {
        $long = $this->service(40);
        $short = $this->service(20);

        $this->book($long, '08:00')->assertCreated();
        $this->assertFalse(Slot::where('start_time', '08:00:00')->first()->is_available);
        $this->assertTrue(Slot::where('start_time', '08:30:00')->first()->is_available);

        $this->book($short, '08:40')->assertCreated();
        $this->assertFalse(Slot::where('start_time', '08:30:00')->first()->is_available);
    }

    public function test_times_reject_service_not_offered_by_worker(): void
    {
        $other = Service::factory()->create(['is_active' => true]);

        $this->getJson("/api/slots/times?worker_id={$this->worker->id}&service_id={$other->id}")
            ->assertUnprocessable();
    }

    public function test_existing_booking_without_service_keeps_slot_times(): void
    {
        $slot = Slot::where('start_time', '09:00:00')->firstOrFail();
        $booking = Booking::factory()->create(['slot_id' => $slot->id, 'status' => 'confirmed']);

        $this->assertSame('09:00:00', $booking->start_time);
        $this->assertNotContains('09:00:00', $this->offeredStarts(null));
    }
}
