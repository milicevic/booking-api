<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.platform_host' => 'slotres.click', 'app.tenant_site_port' => null]);
        $this->tenant->update(['subdomain' => 'salon-ana']);
        $this->user = User::factory()->client()->unverified()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'ana@salon.com',
            'password' => bcrypt('stara-lozinka'),
        ]);
    }

    private function requestToken(): string
    {
        Notification::fake();
        $this->postJson('/api/auth/password/forgot', ['email' => 'ana@salon.com'])->assertOk();

        $token = null;
        Notification::assertSentTo($this->user, ResetPasswordLink::class, function (ResetPasswordLink $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        return (string) $token;
    }

    public function test_link_points_to_the_business_site(): void
    {
        $token = $this->requestToken();

        $mail = (new ResetPasswordLink($token))->toMail($this->user);

        $this->assertStringStartsWith('https://salon-ana.slotres.click/admin/reset-password?token=', $mail->actionUrl);
        $this->assertStringContainsString('email=ana%40salon.com', $mail->actionUrl);
    }

    public function test_unknown_email_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();

        $known = $this->postJson('/api/auth/password/forgot', ['email' => 'ana@salon.com'])->json('message');
        $unknown = $this->postJson('/api/auth/password/forgot', ['email' => 'nepostoji@salon.com'])->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTimes(ResetPasswordLink::class, 1);
    }

    public function test_valid_token_sets_the_new_password_and_signs_out_everywhere(): void
    {
        $this->user->createToken('api-token');
        $token = $this->requestToken();

        $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => 'ana@salon.com',
            'password' => 'nova-lozinka',
            'password_confirmation' => 'nova-lozinka',
        ])->assertOk();

        $user = $this->user->fresh();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotNull($user->email_verified_at);
        $this->postJson('/api/auth/login', ['email' => 'ana@salon.com', 'password' => 'nova-lozinka'])->assertOk();
    }

    public function test_token_works_only_once(): void
    {
        $token = $this->requestToken();
        $payload = ['token' => $token, 'email' => 'ana@salon.com', 'password' => 'nova-lozinka', 'password_confirmation' => 'nova-lozinka'];

        $this->postJson('/api/auth/password/reset', $payload)->assertOk();
        $this->postJson('/api/auth/password/reset', $payload)->assertUnprocessable();
    }

    public function test_invalid_or_expired_token_is_rejected(): void
    {
        $token = $this->requestToken();
        $payload = ['email' => 'ana@salon.com', 'password' => 'nova-lozinka', 'password_confirmation' => 'nova-lozinka'];

        $this->withHeader('Accept-Language', 'sr')
            ->postJson('/api/auth/password/reset', ['token' => 'pogresan', ...$payload])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Link za promenu lozinke nije važeći.');

        $this->travel(61)->minutes();
        $this->postJson('/api/auth/password/reset', ['token' => $token, ...$payload])->assertUnprocessable();
    }

    public function test_password_must_be_confirmed_and_long_enough(): void
    {
        $this->postJson('/api/auth/password/reset', [
            'token' => 'x', 'email' => 'ana@salon.com', 'password' => 'kratka', 'password_confirmation' => 'druga',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }
}
