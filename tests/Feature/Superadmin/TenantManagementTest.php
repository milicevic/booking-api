<?php

namespace Tests\Feature\Superadmin;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Slot;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WelcomeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->superadmin()->create(['tenant_id' => null]);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'app_name' => 'Salon Ana',
            'name' => 'Ana Anić',
            'email' => 'ana@salon.com',
            'password' => 'tajna1234',
        ], $overrides);
    }

    // --- Kreiranje ---

    public function test_superadmin_can_create_tenant_with_client(): void
    {
        Notification::fake();

        $this->actingAs($this->superadmin())
            ->postJson('/api/superadmin/tenants', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('subdomain', 'salon-ana')
            ->assertJsonPath('deploy_status', 'deployed')
            ->assertJsonPath('users.0.email', 'ana@salon.com');

        $tenant = Tenant::where('subdomain', 'salon-ana')->firstOrFail();
        $client = User::withoutGlobalScopes()->where('email', 'ana@salon.com')->firstOrFail();
        $this->assertSame('client', $client->role);
        $this->assertSame($tenant->id, $client->tenant_id);
        Notification::assertSentTo($client, WelcomeClient::class);
    }

    public function test_superadmin_can_choose_subdomain(): void
    {
        Notification::fake();

        $this->actingAs($this->superadmin())
            ->postJson('/api/superadmin/tenants', $this->validPayload(['subdomain' => 'ana']))
            ->assertCreated()
            ->assertJsonPath('subdomain', 'ana');
    }

    public function test_created_tenant_site_is_available_immediately(): void
    {
        Notification::fake();

        $this->actingAs($this->superadmin())
            ->postJson('/api/superadmin/tenants', $this->validPayload());

        $this->getJson('http://salon-ana.slotres.click/api/tenant/config')
            ->assertOk()
            ->assertJsonPath('app_name', 'Salon Ana');
    }

    public function test_create_validates_taken_subdomain_and_email(): void
    {
        User::factory()->client()->create(['email' => 'ana@salon.com']);

        $this->actingAs($this->superadmin())
            ->postJson('/api/superadmin/tenants', $this->validPayload(['subdomain' => 'localhost']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subdomain', 'email']);
    }

    public function test_client_cannot_create_tenant(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->postJson('/api/superadmin/tenants', $this->validPayload())
            ->assertForbidden();
    }

    // --- Brisanje ---

    public function test_superadmin_can_delete_tenant_with_all_data(): void
    {
        Storage::fake('public');
        $tenant = Tenant::factory()->create();
        app()->instance('current_tenant', $tenant);

        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $client->createToken('api-token');
        $service = Service::factory()->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id]);
        $booking = Booking::factory()->create(['slot_id' => $slot->id]);
        $booking->updatePushSubscription('https://push.example.com/abc');
        Storage::disk('public')->put("logos/{$tenant->id}/logo.webp", 'x');

        app()->instance('current_tenant', $this->tenant);
        $otherClient = User::factory()->client()->create();

        $this->actingAs($this->superadmin())
            ->deleteJson("/api/superadmin/tenants/{$tenant->id}")
            ->assertNoContent();

        $this->assertModelMissing($tenant);
        $this->assertDatabaseMissing('users', ['id' => $client->id]);
        $this->assertDatabaseMissing('users', ['id' => $worker->id]);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        $this->assertDatabaseMissing('slots', ['id' => $slot->id]);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('push_subscriptions', 0);
        Storage::disk('public')->assertMissing("logos/{$tenant->id}/logo.webp");

        $this->assertDatabaseHas('users', ['id' => $otherClient->id]);
        $this->assertModelExists($this->tenant);
    }

    public function test_deleted_tenant_site_is_no_longer_available(): void
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'salon-ana']);

        $this->actingAs($this->superadmin())
            ->deleteJson("/api/superadmin/tenants/{$tenant->id}")
            ->assertNoContent();

        $this->getJson('http://salon-ana.slotres.click/api/slots/available')
            ->assertNotFound();
    }

    public function test_client_cannot_delete_tenant(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->deleteJson("/api/superadmin/tenants/{$this->tenant->id}")
            ->assertForbidden();

        $this->assertModelExists($this->tenant);
    }
}
