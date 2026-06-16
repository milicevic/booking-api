<?php

namespace Tests\Feature\Service;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    // --- index ---

    public function test_client_can_list_all_tenant_services(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        Service::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($client)->getJson('/api/services');

        $response->assertOk()->assertJsonCount(3);
    }

    public function test_client_can_filter_services_by_worker(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);

        $assigned = Service::factory()->create(['tenant_id' => $this->tenant->id]);
        Service::factory()->create(['tenant_id' => $this->tenant->id]); // unassigned

        $worker->services()->attach($assigned->id);

        $response = $this->actingAs($client)->getJson('/api/services?worker_id='.$worker->id);

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $assigned->id);
    }

    public function test_worker_can_see_all_tenant_services(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        Service::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($worker)->getJson('/api/services');

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_unauthenticated_cannot_list_services(): void
    {
        $this->getJson('/api/services')->assertUnauthorized();
    }

    // --- store ---

    public function test_client_can_create_service(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($client)->postJson('/api/services', [
            'name' => 'Šišanje',
            'duration_minutes' => 45,
            'price' => 20.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Šišanje')
            ->assertJsonPath('duration_minutes', 45);

        $this->assertDatabaseHas('services', ['name' => 'Šišanje', 'tenant_id' => $this->tenant->id]);
    }

    public function test_worker_cannot_create_service(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($worker)->postJson('/api/services', [
            'name' => 'Masaža',
            'duration_minutes' => 60,
        ])->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)->postJson('/api/services', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'duration_minutes']);
    }

    public function test_price_is_optional(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($client)->postJson('/api/services', [
            'name' => 'Usluga bez cene',
            'duration_minutes' => 30,
        ]);

        $response->assertCreated()->assertJsonPath('price', null);
    }

    // --- update ---

    public function test_client_can_update_service(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Staro']);

        $response = $this->actingAs($client)
            ->patchJson('/api/services/'.$service->id, ['name' => 'Novo']);

        $response->assertOk()->assertJsonPath('name', 'Novo');
    }

    public function test_worker_cannot_update_service(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($worker)
            ->patchJson('/api/services/'.$service->id, ['name' => 'Hack'])
            ->assertForbidden();
    }

    // --- destroy ---

    public function test_client_can_delete_service(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->deleteJson('/api/services/'.$service->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_worker_cannot_delete_service(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($worker)
            ->deleteJson('/api/services/'.$service->id)
            ->assertForbidden();
    }

    // --- assign/unassign worker ---

    public function test_client_can_assign_service_to_worker(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->postJson('/api/services/'.$service->id.'/workers/'.$worker->id)
            ->assertOk();

        $this->assertDatabaseHas('worker_services', ['worker_id' => $worker->id, 'service_id' => $service->id]);
    }

    public function test_assigning_same_service_twice_does_not_duplicate(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)->postJson('/api/services/'.$service->id.'/workers/'.$worker->id);
        $this->actingAs($client)->postJson('/api/services/'.$service->id.'/workers/'.$worker->id);

        $this->assertDatabaseCount('worker_services', 1);
    }

    public function test_client_can_unassign_service_from_worker(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);
        $worker->services()->attach($service->id);

        $this->actingAs($client)
            ->deleteJson('/api/services/'.$service->id.'/workers/'.$worker->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('worker_services', ['worker_id' => $worker->id, 'service_id' => $service->id]);
    }

    public function test_client_cannot_assign_service_to_another_clients_worker(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $otherClient = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($otherClient->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->postJson('/api/services/'.$service->id.'/workers/'.$worker->id)
            ->assertForbidden();
    }

    public function test_worker_cannot_assign_service_to_worker(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($worker)
            ->postJson('/api/services/'.$service->id.'/workers/'.$worker->id)
            ->assertForbidden();
    }
}
