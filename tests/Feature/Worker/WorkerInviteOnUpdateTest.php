<?php

namespace Tests\Feature\Worker;

use App\Models\User;
use App\Notifications\WorkerInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Radnik dobija nalog samo preko pozivnice na email; email dodat ili promenjen kasnije šalje novu pozivnicu.
 */
class WorkerInviteOnUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->client = User::factory()->client()->create();
    }

    public function test_adding_email_to_worker_without_account_sends_invite(): void
    {
        $worker = User::factory()->worker($this->client->id)->create(['email' => null, 'invite_token' => null]);

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['email' => 'marko@example.com'])->assertOk();

        $worker->refresh();
        $this->assertSame('marko@example.com', $worker->email);
        $this->assertNotNull($worker->invite_token);
        Notification::assertSentTo($worker, WorkerInvite::class);

        $this->postJson('/api/auth/accept-invite', ['invite_token' => $worker->invite_token, 'password' => 'Lozinka123!', 'password_confirmation' => 'Lozinka123!'])
            ->assertOk();
    }

    public function test_changing_email_before_activation_replaces_old_invite(): void
    {
        $worker = User::factory()->worker($this->client->id)->create(['email' => 'stari@example.com', 'invite_token' => 'stari-token']);

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['email' => 'novi@example.com'])->assertOk();

        $this->assertNotSame('stari-token', $worker->fresh()->invite_token);
        Notification::assertSentTo($worker, WorkerInvite::class);
        $this->postJson('/api/auth/accept-invite', ['invite_token' => 'stari-token', 'password' => 'Lozinka123!', 'password_confirmation' => 'Lozinka123!'])
            ->assertNotFound();
    }

    public function test_same_email_or_other_fields_do_not_resend_invite(): void
    {
        $worker = User::factory()->worker($this->client->id)->create(['email' => 'marko@example.com', 'invite_token' => 'token']);

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['name' => 'Marko', 'email' => 'marko@example.com'])->assertOk();

        $this->assertSame('token', $worker->fresh()->invite_token);
        Notification::assertNothingSent();
    }

    public function test_removing_email_before_activation_cancels_invite(): void
    {
        $worker = User::factory()->worker($this->client->id)->create(['email' => 'marko@example.com', 'invite_token' => 'token']);

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['email' => null])->assertOk();

        $worker->refresh();
        $this->assertNull($worker->email);
        $this->assertNull($worker->invite_token);
        Notification::assertNothingSent();
    }

    public function test_activated_worker_changing_email_gets_no_invite(): void
    {
        $worker = User::factory()->worker($this->client->id)->create(['email' => 'marko@example.com', 'password' => 'Lozinka123!']);

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['email' => 'marko.novi@example.com'])->assertOk();

        $this->assertSame('marko.novi@example.com', $worker->fresh()->email);
        $this->assertNull($worker->fresh()->invite_token);
        Notification::assertNothingSent();
    }

    public function test_activated_worker_email_cannot_be_removed(): void
    {
        $worker = User::factory()->worker($this->client->id)->create(['email' => 'marko@example.com', 'password' => 'Lozinka123!']);

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['email' => null])->assertUnprocessable();

        $this->assertSame('marko@example.com', $worker->fresh()->email);
    }
}
