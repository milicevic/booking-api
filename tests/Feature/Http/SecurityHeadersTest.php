<?php

namespace Tests\Feature\Http;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_get_strict_headers(): void
    {
        $this->getJson('/api/tenant/config')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_html_pages_allow_only_their_own_inline_styles_and_forms(): void
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $booking = Booking::factory()->create(['slot_id' => Slot::factory()->create(['worker_id' => $worker->id])->id]);

        $csp = (string) $this->get("/manage/{$booking->token}")->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("style-src 'unsafe-inline'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_hsts_is_sent_over_https(): void
    {
        $this->get('https://localhost/api/tenant/config')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
