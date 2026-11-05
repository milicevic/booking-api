<?php

namespace Tests\Feature\Worker;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_list_does_not_expose_secrets(): void
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create(['invite_token' => 'secret-invite', 'google_id' => 'google-123']);
        WorkerProfile::factory()->create(['user_id' => $worker->id, 'phone' => '0601234567']);

        $response = $this->actingAs($client)->getJson('/api/workers')->assertOk();

        $response->assertJsonPath('0.id', $worker->id)
            ->assertJsonPath('0.role', 'worker')
            ->assertJsonPath('0.worker_profile.phone', '0601234567')
            ->assertJsonMissingPath('0.invite_token')
            ->assertJsonMissingPath('0.password')
            ->assertJsonMissingPath('0.google_id');
    }

    public function test_worker_responses_are_not_wrapped_in_data(): void
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();

        $this->actingAs($client)->getJson("/api/workers/{$worker->id}")
            ->assertOk()
            ->assertJsonMissingPath('data')
            ->assertJsonPath('id', $worker->id);
    }

    public function test_client_cannot_view_another_clients_worker(): void
    {
        $client = User::factory()->client()->create();
        $otherWorker = User::factory()->worker(User::factory()->client()->create()->id)->create();

        $this->actingAs($client)->getJson("/api/workers/{$otherWorker->id}")->assertForbidden();
    }
}
