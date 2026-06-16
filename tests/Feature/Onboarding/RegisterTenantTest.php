<?php

namespace Tests\Feature\Onboarding;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WelcomeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTenantTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Anić',
            'email' => 'ana@salon.com',
            'password' => 'tajna1234',
            'app_name' => 'Moj Salon',
        ], $overrides);
    }

    public function test_can_register_new_tenant(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'tenant', 'user']);

        $this->assertDatabaseHas('tenants', ['app_name' => 'Moj Salon']);
        $this->assertDatabaseHas('users', ['email' => 'ana@salon.com', 'role' => 'client']);
    }

    public function test_tenant_is_set_to_trialing_with_seven_day_trial(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->validPayload());

        $tenant = Tenant::where('app_name', 'Moj Salon')->firstOrFail();
        $this->assertSame('trialing', $tenant->subscription_status);
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertTrue($tenant->trial_ends_at->isAfter(now()->addDays(6)));
    }

    public function test_user_is_linked_to_tenant(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->validPayload());

        $tenant = Tenant::where('app_name', 'Moj Salon')->firstOrFail();
        $user = User::withoutGlobalScopes()->where('email', 'ana@salon.com')->firstOrFail();
        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_subdomain_is_auto_generated_from_app_name(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->validPayload());

        $this->assertDatabaseHas('tenants', ['subdomain' => 'moj-salon']);
    }

    public function test_custom_subdomain_is_used_when_provided(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->validPayload(['subdomain' => 'moj-frizerski']));

        $this->assertDatabaseHas('tenants', ['subdomain' => 'moj-frizerski']);
    }

    public function test_returns_token_for_immediate_login(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', $this->validPayload());

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_sends_welcome_mail_to_client(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->validPayload());

        $user = User::withoutGlobalScopes()->where('email', 'ana@salon.com')->firstOrFail();
        Notification::assertSentTo($user, WelcomeClient::class);
    }

    public function test_returns_422_when_email_already_taken(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'ana@salon.com']);

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_returns_422_when_subdomain_already_taken(): void
    {
        Notification::fake();

        Tenant::factory()->create(['subdomain' => 'moj-salon']);

        $response = $this->postJson('/api/register', $this->validPayload(['subdomain' => 'moj-salon']));

        $response->assertStatus(422)->assertJsonValidationErrors(['subdomain']);
    }

    public function test_auto_subdomain_is_unique_when_name_is_taken(): void
    {
        Notification::fake();

        Tenant::factory()->create(['subdomain' => 'moj-salon']);

        $this->postJson('/api/register', $this->validPayload(['email' => 'ivo@salon.com']));

        $this->assertDatabaseHas('tenants', ['subdomain' => 'moj-salon-1']);
    }

    public function test_returns_422_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'app_name']);
    }
}
