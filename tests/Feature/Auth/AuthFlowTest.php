<?php

namespace Tests\Feature\Auth;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow_login_me_logout(): void
    {
        $user = User::factory()->client()->create([
            'name' => 'Klijent Test',
            'email' => 'klijent@test.com',
            'password' => bcrypt('tajna'),
        ]);

        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'klijent@test.com',
            'password' => 'tajna',
        ]);

        $loginResponse->assertOk();
        $token = $loginResponse->json('token');
        $this->assertNotEmpty($token);

        // Me
        $meResponse = $this->withToken($token)->getJson('/api/auth/me');

        $meResponse->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', 'klijent@test.com');

        // Logout
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_returns_user_data(): void
    {
        $user = User::factory()->client()->create([
            'name' => 'Ana Anić',
            'email' => 'ana@test.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', 'Ana Anić')
            ->assertJsonPath('email', 'ana@test.com')
            ->assertJsonPath('role', 'client');
    }

    public function test_me_includes_client_profile(): void
    {
        $user = User::factory()->client()->create();
        ClientProfile::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'Moj Salon',
            'auto_confirm_bookings' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('client_profile.business_name', 'Moj Salon')
            ->assertJsonPath('client_profile.auto_confirm_bookings', true);
    }

    public function test_me_returns_null_client_profile_when_not_set(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('client_profile', null);
    }

    public function test_me_does_not_expose_password(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonMissingPath('password');
    }

    public function test_me_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_token_from_login_works_with_me_endpoint(): void
    {
        User::factory()->client()->create([
            'email' => 'klijent@test.com',
            'password' => bcrypt('tajna'),
        ]);

        $token = $this->postJson('/api/auth/login', [
            'email' => 'klijent@test.com',
            'password' => 'tajna',
        ])->json('token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();
    }
}
