<?php

namespace Tests\Feature\Onboarding;

use App\Enums\BusinessType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Vrsta delatnosti firme (za predloge usluga u onboardingu).
 */
class BusinessTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_stores_business_type(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Ana Anić',
            'email' => 'ana@salon.com',
            'password' => 'tajna1234',
            'app_name' => 'Moj Salon',
            'business_type' => 'barber',
        ])->assertCreated();

        $this->assertSame(BusinessType::Barber, Tenant::where('app_name', 'Moj Salon')->firstOrFail()->business_type);
    }

    public function test_business_type_is_optional_and_validated(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Ana Anić',
            'email' => 'ana@salon.com',
            'password' => 'tajna1234',
            'app_name' => 'Moj Salon',
            'business_type' => 'bakery',
        ])->assertUnprocessable()->assertJsonValidationErrors('business_type');
    }

    public function test_owner_can_change_business_type_and_config_returns_it(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->withHeader('X-Tenant-Subdomain', 'localhost')
            ->patchJson('/api/client/branding', ['business_type' => 'dentist'])
            ->assertOk()
            ->assertJsonPath('business_type', 'dentist');

        $this->withHeader('X-Tenant-Subdomain', 'localhost')->getJson('/api/tenant/config')
            ->assertJsonPath('business_type', 'dentist');
    }
}
