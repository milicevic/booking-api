<?php

namespace Tests\Feature\Onboarding;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Naziv firme je jedinstven (bez obzira na velika/mala slova), a lozinke moraju biti dovoljno jake.
 */
class BusinessNameAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Ana Anić',
            'email' => 'ana@salon.com',
            'password' => 'Tajna12345',
            'app_name' => 'Salon Ana',
            ...$overrides,
        ];
    }

    public function test_registration_rejects_taken_business_name_in_any_case(): void
    {
        Tenant::factory()->create(['app_name' => 'Salon Ana']);

        $this->postJson('/api/register', $this->payload(['app_name' => '  salon ANA ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('app_name')
            ->assertJsonPath('errors.app_name.0', __('messages.business_name_taken'));
    }

    public function test_admin_cannot_create_tenant_with_taken_name(): void
    {
        Tenant::factory()->create(['app_name' => 'Salon Ana']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/tenants', $this->payload(['app_name' => 'SALON ANA']))
            ->assertJsonValidationErrors('app_name');
    }

    public function test_owner_can_keep_own_name_but_not_take_another(): void
    {
        $this->tenant->update(['app_name' => 'Moj Salon']);
        Tenant::factory()->create(['app_name' => 'Tuđi Salon']);
        $client = User::factory()->client()->create();

        $this->actingAs($client)->withHeader('X-Tenant-Subdomain', 'localhost')
            ->patchJson('/api/client/branding', ['app_name' => 'moj salon'])
            ->assertOk();

        $this->actingAs($client)->withHeader('X-Tenant-Subdomain', 'localhost')
            ->patchJson('/api/client/branding', ['app_name' => 'Tuđi Salon'])
            ->assertJsonValidationErrors('app_name');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Tajna123'],
            'no uppercase' => ['tajna12345'],
            'no lowercase' => ['TAJNA12345'],
            'no number' => ['TajnaTajna'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_weak_passwords_are_rejected_on_registration(string $password): void
    {
        $this->postJson('/api/register', $this->payload(['password' => $password]))
            ->assertJsonValidationErrors('password');
    }

    public function test_strong_password_is_accepted(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->payload())->assertCreated();
    }

    public function test_weak_password_is_rejected_when_accepting_invite_and_resetting(): void
    {
        $this->postJson('/api/auth/accept-invite', ['invite_token' => 'x', 'password' => 'lozinka123'])
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/auth/password/reset', [
            'token' => 'x', 'email' => 'ana@salon.com', 'password' => 'kratka1A', 'password_confirmation' => 'kratka1A',
        ])->assertJsonValidationErrors('password');
    }
}
