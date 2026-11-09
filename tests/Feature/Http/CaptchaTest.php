<?php

namespace Tests\Feature\Http;

use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function book(?string $captchaToken): TestResponse
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'start_time' => '09:00:00', 'end_time' => '10:00:00']);
        $service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 60]);
        $worker->services()->attach($service->id);

        return $this->postJson('/api/bookings', array_filter([
            'slot_id' => $slot->id,
            'service_id' => $service->id,
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
            'captcha_token' => $captchaToken,
        ]));
    }

    public function test_without_secret_key_captcha_is_off(): void
    {
        Http::fake();

        $this->book(null)->assertCreated();

        Http::assertNothingSent();
    }

    public function test_valid_token_is_verified_with_cloudflare(): void
    {
        config(['services.turnstile.secret' => 'tajna']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->book('dobar-token')->assertCreated();

        Http::assertSent(fn (Request $request): bool => $request['secret'] === 'tajna' && $request['response'] === 'dobar-token');
    }

    public function test_missing_or_rejected_token_blocks_the_booking(): void
    {
        config(['services.turnstile.secret' => 'tajna']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->book(null)->assertUnprocessable()->assertJsonValidationErrors('captcha_token');
        $this->book('los-token')->assertUnprocessable()->assertJsonValidationErrors('captcha_token');
    }

    public function test_registration_is_protected_too(): void
    {
        config(['services.turnstile.secret' => 'tajna']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->postJson('/api/register', [
            'name' => 'Ana', 'email' => 'ana@salon.com', 'password' => 'tajna1234', 'app_name' => 'Salon',
        ])->assertUnprocessable()->assertJsonValidationErrors('captcha_token');
    }

    public function test_cloudflare_outage_does_not_block_bookings(): void
    {
        config(['services.turnstile.secret' => 'tajna']);
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->book('token')->assertCreated();
    }
}
