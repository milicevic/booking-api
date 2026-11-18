<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendTestPushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'webpush.vapid.public_key' => 'public',
            'webpush.vapid.private_key' => 'private',
            'webpush.vapid.subject' => 'mailto:admin@example.com',
        ]);
    }

    public function test_reports_missing_vapid_keys(): void
    {
        config(['webpush.vapid.private_key' => null, 'webpush.vapid.subject' => '']);

        $this->artisan('push:test', ['email' => 'ana@example.com'])
            ->expectsOutputToContain('Not set: VAPID_PRIVATE_KEY, VAPID_SUBJECT')
            ->assertFailed();
    }

    public function test_reports_unknown_user(): void
    {
        $this->artisan('push:test', ['email' => 'nobody@example.com'])
            ->expectsOutputToContain('User not found')
            ->assertFailed();
    }

    public function test_reports_user_without_devices(): void
    {
        User::factory()->client()->create(['email' => 'ana@example.com']);

        $this->artisan('push:test', ['email' => 'ana@example.com'])
            ->expectsOutputToContain('Devices with notifications enabled: 0')
            ->assertFailed();
    }

    public function test_vapid_endpoint_returns_503_without_key(): void
    {
        config(['webpush.vapid.public_key' => null]);

        $this->getJson('/api/push/vapid-public-key')
            ->assertStatus(503)
            ->assertJsonPath('message', __('messages.push_not_configured'));
    }

    public function test_vapid_endpoint_returns_public_key(): void
    {
        $this->getJson('/api/push/vapid-public-key')->assertExactJson(['vapid_public_key' => 'public']);
    }
}
