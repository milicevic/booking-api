<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WelcomeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.platform_host' => 'slotres.click',
            'app.frontend_url' => 'https://slotres.click',
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://api.slotres.click/api/auth/google/callback',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function state(array $overrides = []): string
    {
        return Crypt::encryptString(json_encode(array_merge([
            'intent' => 'login',
            'host' => 'slotres.click',
            'app_name' => null,
            'exp' => now()->addMinutes(10)->timestamp,
        ], $overrides)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fakeGoogleUser(array $attributes = []): void
    {
        Socialite::fake('google', SocialiteUser::fake(array_merge([
            'id' => 'google-123',
            'name' => 'Ana Anić',
            'email' => 'ana@test.com',
            'email_verified' => true,
        ], $attributes)));
    }

    private function fragmentOf(string $location): array
    {
        parse_str((string) parse_url($location, PHP_URL_FRAGMENT), $fragment);

        return $fragment;
    }

    public function test_redirect_sends_encrypted_state_to_google(): void
    {
        $response = $this->get('/api/auth/google/redirect?intent=register&host=slotres.click&app_name=Salon+Ana');

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $state = json_decode(Crypt::decryptString($query['state']), true);

        $this->assertSame('register', $state['intent']);
        $this->assertSame('slotres.click', $state['host']);
        $this->assertSame('Salon Ana', $state['app_name']);
        $this->assertGreaterThan(now()->timestamp, $state['exp']);
    }

    public function test_redirect_allows_login_from_tenant_subdomain_and_custom_domain(): void
    {
        Tenant::factory()->create(['subdomain' => 'salon-ana', 'custom_domain' => 'rezervacije.salon-ana.com']);
        Socialite::fake('google');

        $this->get('/api/auth/google/redirect?intent=login&host=salon-ana.slotres.click:5173')
            ->assertRedirect('https://socialite.fake/google/authorize');
        $this->get('/api/auth/google/redirect?intent=login&host=rezervacije.salon-ana.com')
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_redirect_rejects_unknown_host(): void
    {
        $this->get('/api/auth/google/redirect?intent=login&host=evil.com')
            ->assertRedirect('https://slotres.click/auth/callback#error=invalid_host');

        $this->get('/api/auth/google/redirect?intent=login&host='.urlencode('slotres.click@evil.com'))
            ->assertRedirect('https://slotres.click/auth/callback#error=invalid_host');
    }

    public function test_redirect_rejects_register_from_tenant_site(): void
    {
        Tenant::factory()->create(['subdomain' => 'salon-ana']);

        $this->get('/api/auth/google/redirect?intent=register&host=salon-ana.slotres.click&app_name=Novi')
            ->assertRedirect('https://salon-ana.slotres.click/auth/callback#error=invalid_host');
    }

    public function test_redirect_requires_app_name_for_register(): void
    {
        $this->get('/api/auth/google/redirect?intent=register&host=slotres.click')
            ->assertRedirect('https://slotres.click/auth/callback#error=google_failed');
    }

    public function test_existing_google_user_can_login(): void
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'salon-ana']);
        $user = User::factory()->client()->create(['tenant_id' => $tenant->id, 'google_id' => 'google-123', 'email' => 'drugi@test.com']);
        $this->fakeGoogleUser();

        $response = $this->get('/api/auth/google/callback?state='.urlencode($this->state(['host' => 'salon-ana.slotres.click'])));

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://salon-ana.slotres.click/auth/callback#token=', $location);
        $this->assertNotEmpty($this->fragmentOf($location)['token']);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_links_existing_account_by_verified_email(): void
    {
        $user = User::factory()->client()->create(['email' => 'ana@test.com', 'google_id' => null]);
        $this->fakeGoogleUser();

        $location = $this->get('/api/auth/google/callback?state='.urlencode($this->state()))
            ->assertRedirect()->headers->get('Location');

        $this->assertArrayHasKey('token', $this->fragmentOf($location));
        $this->assertSame('google-123', $user->fresh()->google_id);
    }

    public function test_register_creates_tenant_and_client_and_sends_welcome(): void
    {
        Notification::fake();
        $this->fakeGoogleUser();

        $location = $this->get('/api/auth/google/callback?state='.urlencode($this->state(['intent' => 'register', 'app_name' => 'Salon Ana'])))
            ->assertRedirect()->headers->get('Location');

        $this->assertArrayHasKey('token', $this->fragmentOf($location));

        $user = User::withoutGlobalScopes()->where('email', 'ana@test.com')->firstOrFail();
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->tenant_id);
        $this->assertSame('Salon Ana', $user->tenant->app_name);

        Notification::assertSentTo($user, WelcomeClient::class);
    }

    public function test_login_without_account_returns_account_not_found(): void
    {
        $tenantCount = Tenant::count();
        $this->fakeGoogleUser();

        $this->get('/api/auth/google/callback?state='.urlencode($this->state()))
            ->assertRedirect('https://slotres.click/auth/callback#error=account_not_found');

        $this->assertDatabaseMissing('users', ['email' => 'ana@test.com']);
        $this->assertSame($tenantCount, Tenant::count());
    }

    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->client()->create(['email' => 'ana@test.com', 'is_suspended' => true]);
        $this->fakeGoogleUser();

        $this->get('/api/auth/google/callback?state='.urlencode($this->state()))
            ->assertRedirect('https://slotres.click/auth/callback#error=suspended');
    }

    public function test_user_of_another_tenant_cannot_login(): void
    {
        Tenant::factory()->create(['subdomain' => 'salon-ana']);
        $otherTenant = Tenant::factory()->create(['subdomain' => 'salon-maja']);
        User::factory()->client()->create(['email' => 'ana@test.com', 'tenant_id' => $otherTenant->id]);
        $this->fakeGoogleUser();

        $this->get('/api/auth/google/callback?state='.urlencode($this->state(['host' => 'salon-ana.slotres.click'])))
            ->assertRedirect('https://salon-ana.slotres.click/auth/callback#error=wrong_tenant');
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $user = User::factory()->client()->create(['email' => 'ana@test.com', 'google_id' => null]);
        $this->fakeGoogleUser(['email_verified' => false]);

        $this->get('/api/auth/google/callback?state='.urlencode($this->state()))
            ->assertRedirect('https://slotres.click/auth/callback#error=email_not_verified');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_invalid_state_is_rejected(): void
    {
        $this->fakeGoogleUser();

        $this->get('/api/auth/google/callback?state=neispravan')
            ->assertRedirect('https://slotres.click/auth/callback#error=google_failed');
        $this->get('/api/auth/google/callback')
            ->assertRedirect('https://slotres.click/auth/callback#error=google_failed');
    }

    public function test_expired_state_is_rejected(): void
    {
        User::factory()->client()->create(['email' => 'ana@test.com']);
        $this->fakeGoogleUser();

        $this->get('/api/auth/google/callback?state='.urlencode($this->state(['exp' => now()->subMinute()->timestamp])))
            ->assertRedirect('https://slotres.click/auth/callback#error=google_failed');
    }

    public function test_google_failure_returns_google_failed(): void
    {
        Socialite::fake('google', fn () => throw new \RuntimeException('access_denied'));

        $this->get('/api/auth/google/callback?state='.urlencode($this->state()))
            ->assertRedirect('https://slotres.click/auth/callback#error=google_failed');
    }

    public function test_password_login_still_rejects_google_only_account(): void
    {
        User::factory()->client()->create(['email' => 'ana@test.com', 'password' => null, 'google_id' => 'google-123']);

        $this->postJson('/api/auth/login', ['email' => 'ana@test.com', 'password' => 'bilošta'])
            ->assertStatus(401);
    }

    public function test_google_login_confirms_unverified_account(): void
    {
        $user = User::factory()->client()->unverified()->create(['email' => 'ana@test.com']);
        $this->fakeGoogleUser();

        $location = $this->get('/api/auth/google/callback?state='.urlencode($this->state()))
            ->assertRedirect()->headers->get('Location');

        $this->assertArrayHasKey('token', $this->fragmentOf($location));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_registration_is_confirmed_immediately(): void
    {
        Notification::fake();
        $this->fakeGoogleUser();

        $this->get('/api/auth/google/callback?state='.urlencode($this->state(['intent' => 'register', 'app_name' => 'Salon Ana'])));

        $this->assertNotNull(User::withoutGlobalScopes()->where('email', 'ana@test.com')->value('email_verified_at'));
    }
}
