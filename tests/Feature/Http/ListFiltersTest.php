<?php

namespace Tests\Feature\Http;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filteri i straničenje za GET /api/slots i GET /api/bookings (kalendar).
 */
class ListFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $ana;

    private User $marko;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create();
        $this->ana = User::factory()->worker($this->client->id)->create();
        $this->marko = User::factory()->worker($this->client->id)->create();

        foreach ([[$this->ana, '2026-12-01'], [$this->ana, '2026-12-08'], [$this->marko, '2026-12-02']] as [$worker, $date]) {
            $slot = Slot::factory()->create(['worker_id' => $worker->id, 'date' => $date]);
            Booking::factory()->create(['slot_id' => $slot->id, 'status' => $date === '2026-12-08' ? 'cancelled' : 'confirmed']);
        }
    }

    public function test_without_filters_lists_return_plain_arrays_as_before(): void
    {
        $this->actingAs($this->client)->getJson('/api/slots')->assertJsonCount(3);
        $this->actingAs($this->client)->getJson('/api/bookings')->assertJsonCount(3)->assertJsonMissingPath('meta');
    }

    public function test_slots_filtered_by_date_range_and_worker(): void
    {
        $this->actingAs($this->client)->getJson('/api/slots?from=2026-12-01&to=2026-12-07')->assertJsonCount(2);
        $this->actingAs($this->client)->getJson("/api/slots?worker_id={$this->ana->id}")->assertJsonCount(2);
        $this->actingAs($this->client)->getJson("/api/slots?worker_id={$this->ana->id}&to=2026-12-05")
            ->assertJsonCount(1)
            ->assertJsonPath('0.date', '2026-12-01');
    }

    public function test_bookings_filtered_by_date_worker_and_status(): void
    {
        $this->actingAs($this->client)->getJson('/api/bookings?from=2026-12-02')->assertJsonCount(2);
        $this->actingAs($this->client)->getJson("/api/bookings?worker_id={$this->marko->id}")->assertJsonCount(1);
        $this->actingAs($this->client)->getJson('/api/bookings?status=cancelled')
            ->assertJsonCount(1)
            ->assertJsonPath('0.slot.date', '2026-12-08');
    }

    public function test_bookings_always_include_worker_and_service(): void
    {
        $this->actingAs($this->client)->getJson('/api/bookings')
            ->assertJsonStructure([['slot' => ['worker' => ['id', 'name']], 'service']]);
    }

    public function test_per_page_returns_paginated_response(): void
    {
        $this->actingAs($this->client)->getJson('/api/bookings?per_page=2')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($this->client)->getJson('/api/slots?per_page=2&page=2')
            ->assertJsonCount(1, 'data');
    }

    public function test_worker_filter_cannot_reveal_colleagues_data_to_worker(): void
    {
        $this->actingAs($this->ana)->getJson("/api/bookings?worker_id={$this->marko->id}")->assertJsonCount(0);
        $this->actingAs($this->ana)->getJson("/api/slots?worker_id={$this->marko->id}")->assertJsonCount(0);
        $this->actingAs($this->ana)->getJson('/api/bookings')->assertJsonCount(2);
    }

    public function test_worker_filter_cannot_reveal_other_business(): void
    {
        $other = User::factory()->client()->create();

        $this->actingAs($other)->getJson("/api/slots?worker_id={$this->ana->id}")->assertJsonCount(0);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->client)
            ->getJson('/api/bookings?from=01.12.2026&status=unknown&per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['from', 'status', 'per_page']);
    }
}
