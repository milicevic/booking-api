<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->client()->create([
            'email' => 'klijent@test.com',
            'password' => bcrypt('tajna'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'klijent@test.com',
            'password' => 'tajna',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'client'])
            ->assertJsonPath('client.email', 'klijent@test.com');
    }

    public function test_returns_token_on_successful_login(): void
    {
        User::factory()->client()->create([
            'email' => 'klijent@test.com',
            'password' => bcrypt('tajna'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'klijent@test.com',
            'password' => 'tajna',
        ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
    }

    public function test_returns_401_with_wrong_password(): void
    {
        User::factory()->client()->create([
            'email' => 'klijent@test.com',
            'password' => bcrypt('tajna'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'klijent@test.com',
            'password' => 'pogresna',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_401_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nepostoji@test.com',
            'password' => 'tajna',
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_422_when_email_is_missing(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'tajna',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_returns_422_when_password_is_missing(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'klijent@test.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_returns_422_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nije-email',
            'password' => 'tajna',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->client()->create([
            'password' => bcrypt('tajna'),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/auth/logout');

        $response->assertOk()->assertJsonPath('message', __('messages.logged_out'));
    }

    public function test_token_is_deleted_from_database_after_logout(): void
    {
        $user = User::factory()->client()->create([
            'password' => bcrypt('tajna'),
        ]);

        $plainToken = $user->createToken('api-token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($plainToken)->postJson('/api/auth/logout');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}
