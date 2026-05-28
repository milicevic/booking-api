<?php

namespace Tests\Feature\Booking;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_enable_auto_confirm(): void
    {
        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => false]);

        $response = $this->actingAs($client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => true]);

        $response->assertOk();
        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $client->id,
            'auto_confirm_bookings' => true,
        ]);
    }

    public function test_client_can_disable_auto_confirm(): void
    {
        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id, 'auto_confirm_bookings' => true]);

        $response = $this->actingAs($client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => false]);

        $response->assertOk();
        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $client->id,
            'auto_confirm_bookings' => false,
        ]);
    }

    public function test_creates_profile_if_not_exists(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => true]);

        $response->assertOk();
        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $client->id,
            'auto_confirm_bookings' => true,
        ]);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->patchJson('/api/client/settings', ['auto_confirm_bookings' => true]);

        $response->assertStatus(401);
    }

    public function test_returns_422_with_invalid_value(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->patchJson('/api/client/settings', ['auto_confirm_bookings' => 'not-a-boolean']);

        $response->assertStatus(422)->assertJsonValidationErrors(['auto_confirm_bookings']);
    }
}
