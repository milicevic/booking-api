<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_submit_deploy_request(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->postJson('/api/client/deploy-request');

        $response->assertOk();
        $this->assertSame('pending_deploy', $this->tenant->fresh()->deploy_status);
    }

    public function test_client_can_set_subdomain_in_deploy_request(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->postJson('/api/client/deploy-request', [
            'subdomain' => 'moj-salon',
        ]);

        $this->assertSame('moj-salon', $this->tenant->fresh()->subdomain);
    }

    public function test_client_can_set_custom_domain_in_deploy_request(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->postJson('/api/client/deploy-request', [
            'custom_domain' => 'rezervacije.mojsalon.com',
        ]);

        $this->assertSame('rezervacije.mojsalon.com', $this->tenant->fresh()->custom_domain);
    }

    public function test_worker_cannot_submit_deploy_request(): void
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();

        $response = $this->actingAs($worker)->postJson('/api/client/deploy-request');

        $response->assertForbidden();
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/client/deploy-request');

        $response->assertUnauthorized();
    }
}
