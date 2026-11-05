<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use App\Notifications\WelcomeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.platform_host' => 'slotres.click', 'app.tenant_site_port' => null, 'app.frontend_url' => 'https://slotres.click']);
    }

    private function unverifiedClient(): User
    {
        // TestCase već vezuje podrazumevanu firmu kao trenutnu; korisnik pripada njoj
        $this->tenant->update(['subdomain' => 'salon-ana']);

        return User::factory()->client()->unverified()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'ana@salon.com',
            'password' => bcrypt('tajna1234'),
        ]);
    }

    private function verifyUrl(User $user, ?string $hash = null, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->email),
        ]);
    }

    public function test_unverified_user_cannot_login_with_password(): void
    {
        $this->unverifiedClient();

        $this->postJson('/api/auth/login', ['email' => 'ana@salon.com', 'password' => 'tajna1234'])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_wrong_password_does_not_reveal_unverified_account(): void
    {
        $this->unverifiedClient();

        $this->postJson('/api/auth/login', ['email' => 'ana@salon.com', 'password' => 'pogresna'])
            ->assertUnauthorized()
            ->assertJsonMissingPath('code');
    }

    public function test_link_confirms_email_sends_welcome_and_redirects_to_login(): void
    {
        Notification::fake();
        $user = $this->unverifiedClient();

        $this->get($this->verifyUrl($user))
            ->assertRedirect('https://salon-ana.slotres.click/admin/login?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, WelcomeClient::class);

        $this->postJson('/api/auth/login', ['email' => 'ana@salon.com', 'password' => 'tajna1234'])
            ->assertOk();
    }

    public function test_opening_the_link_again_does_not_resend_welcome(): void
    {
        Notification::fake();
        $user = $this->unverifiedClient();
        $url = $this->verifyUrl($user);

        $this->get($url);
        $this->get($url)->assertRedirect('https://salon-ana.slotres.click/admin/login?verified=1');

        Notification::assertSentToTimes($user, WelcomeClient::class, 1);
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = $this->unverifiedClient();

        $this->get($this->verifyUrl($user, minutes: -1))
            ->assertRedirect('https://salon-ana.slotres.click/admin/login?verified=invalid');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_tampered_link_is_rejected(): void
    {
        $user = $this->unverifiedClient();

        $this->get($this->verifyUrl($user, hash: sha1('drugi@salon.com')))
            ->assertRedirect('https://salon-ana.slotres.click/admin/login?verified=invalid');

        $this->get($this->verifyUrl($user).'x')
            ->assertRedirect('https://salon-ana.slotres.click/admin/login?verified=invalid');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_link_for_unknown_user_redirects_to_main_site(): void
    {
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => 999, 'hash' => sha1('x')]);

        $this->get($url)->assertRedirect('https://slotres.click/admin/login?verified=invalid');
    }

    public function test_resend_sends_new_link_to_unverified_account(): void
    {
        Notification::fake();
        $user = $this->unverifiedClient();

        $this->postJson('/api/auth/email/resend', ['email' => 'ana@salon.com'])->assertOk();

        Notification::assertSentTo($user, VerifyEmailAddress::class);
    }

    public function test_resend_gives_same_answer_for_unknown_and_verified_accounts(): void
    {
        Notification::fake();
        $verified = User::factory()->client()->create(['email' => 'potvrdjen@salon.com']);

        $unknown = $this->postJson('/api/auth/email/resend', ['email' => 'nepostoji@salon.com'])->assertOk();
        $known = $this->postJson('/api/auth/email/resend', ['email' => 'potvrdjen@salon.com'])->assertOk();

        $this->assertSame($unknown->json('message'), $known->json('message'));
        Notification::assertNothingSent();
        $this->assertNotNull($verified->fresh()->email_verified_at);
    }

    public function test_accepting_worker_invite_confirms_email(): void
    {
        $this->tenant->update(['subdomain' => 'salon-ana']);
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->unverified()->create([
            'tenant_id' => $this->tenant->id,
            'invite_token' => 'pozivnica-123',
        ]);

        $this->withHeader('X-Tenant-Subdomain', 'salon-ana')
            ->postJson('/api/auth/accept-invite', ['invite_token' => 'pozivnica-123', 'password' => 'tajna1234'])
            ->assertOk();

        $this->assertNotNull($worker->fresh()->email_verified_at);
    }
}
