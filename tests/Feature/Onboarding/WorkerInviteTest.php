<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Notifications\WorkerInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkerInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_invite_is_sent_when_worker_has_email(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();

        $this->actingAs($client)->postJson('/api/workers', [
            'name' => 'Petar Perić',
            'email' => 'petar@salon.com',
        ]);

        $worker = User::where('email', 'petar@salon.com')->firstOrFail();
        Notification::assertSentTo($worker, WorkerInvite::class);
    }

    public function test_invite_is_not_sent_when_worker_has_no_email(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();

        $this->actingAs($client)->postJson('/api/workers', [
            'name' => 'Petar Perić',
        ]);

        Notification::assertNothingSent();
    }

    public function test_invite_token_is_set_on_worker_with_email(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();

        $this->actingAs($client)->postJson('/api/workers', [
            'name' => 'Petar Perić',
            'email' => 'petar@salon.com',
        ]);

        $worker = User::where('email', 'petar@salon.com')->firstOrFail();
        $this->assertNotNull($worker->invite_token);
    }

    public function test_worker_can_accept_invite_and_set_password(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create([
            'email' => 'petar@salon.com',
            'invite_token' => 'valid-token-123',
        ]);

        $response = $this->postJson('/api/auth/accept-invite', [
            'invite_token' => 'valid-token-123',
            'password' => 'novaLozinka123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'invite_token' => null,
        ]);
    }

    public function test_returns_token_after_accepting_invite(): void
    {
        Notification::fake();

        $client = User::factory()->client()->create();
        User::factory()->worker($client->id)->create([
            'email' => 'petar@salon.com',
            'invite_token' => 'valid-token-456',
        ]);

        $response = $this->postJson('/api/auth/accept-invite', [
            'invite_token' => 'valid-token-456',
            'password' => 'novaLozinka123',
        ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_returns_404_for_invalid_invite_token(): void
    {
        $response = $this->postJson('/api/auth/accept-invite', [
            'invite_token' => 'nepostojeci-token',
            'password' => 'novaLozinka123',
        ]);

        $response->assertNotFound();
    }

    public function test_returns_422_when_invite_token_missing(): void
    {
        $response = $this->postJson('/api/auth/accept-invite', [
            'password' => 'novaLozinka123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['invite_token']);
    }

    public function test_returns_422_when_password_too_short(): void
    {
        $response = $this->postJson('/api/auth/accept-invite', [
            'invite_token' => 'some-token',
            'password' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
