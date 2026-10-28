<?php

namespace Tests\Feature\Localization;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function localeProvider(): array
    {
        return [
            'sr' => ['sr-RS,sr;q=0.9', 'sr'],
            'en' => ['en-US,en;q=0.9', 'en'],
        ];
    }

    // --- Validacija ---

    public function test_validation_errors_are_translated_to_serbian(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->withHeader('Accept-Language', 'sr')
            ->postJson('/api/client/branding/logo', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Polje logo je obavezno.')
            ->assertJsonPath('errors.logo.0', 'Polje logo je obavezno.');
    }

    public function test_validation_errors_stay_in_english_by_default(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->postJson('/api/client/branding/logo', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The logo field is required.');
    }

    public function test_validation_uses_translated_attribute_names(): void
    {
        $this->withHeader('Accept-Language', 'sr')
            ->postJson('/api/register', ['name' => 'Marko', 'email' => 'marko@example.com', 'password' => 'secret123'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.app_name.0', 'Polje naziv firme je obavezno.');
    }

    public function test_validation_summary_uses_serbian_plural_forms(): void
    {
        $this->withHeader('Accept-Language', 'sr')
            ->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Polje ime je obavezno. (i još 3 greške)');
    }

    #[DataProvider('localeProvider')]
    public function test_logo_validation_has_custom_messages(string $header, string $locale): void
    {
        Storage::fake('public');
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->withHeader('Accept-Language', $header)
            ->postJson('/api/client/branding/logo', ['logo' => UploadedFile::fake()->image('logo.png', 64, 64)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.logo.0', __('validation.custom.logo.dimensions', [], $locale));
    }

    // --- Generičke HTTP greške ---

    #[DataProvider('localeProvider')]
    public function test_unauthenticated_message_is_translated(string $header, string $locale): void
    {
        $this->withHeader('Accept-Language', $header)
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', __('http.401', [], $locale));
    }

    #[DataProvider('localeProvider')]
    public function test_empty_abort_message_is_translated(string $header, string $locale): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($worker)
            ->withHeader('Accept-Language', $header)
            ->patchJson('/api/client/branding', ['app_name' => 'X'])
            ->assertForbidden()
            ->assertJsonPath('message', __('http.403', [], $locale));
    }

    #[DataProvider('localeProvider')]
    public function test_model_not_found_message_is_translated(string $header, string $locale): void
    {
        $this->withHeader('Accept-Language', $header)
            ->getJson('/api/bookings/nonexistent-token')
            ->assertNotFound()
            ->assertJsonPath('message', __('messages.not_found.booking', [], $locale));
    }

    public function test_cancelling_inactive_booking_returns_specific_message(): void
    {
        $booking = $this->makeBooking('cancelled');

        $this->withHeader('Accept-Language', 'sr')
            ->patchJson("/api/bookings/{$booking->token}/cancel")
            ->assertNotFound()
            ->assertJsonPath('message', 'Rezervacija ne postoji ili više nije aktivna.');
    }

    public function test_route_model_binding_not_found_message_is_translated(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->withHeader('Accept-Language', 'sr')
            ->patchJson('/api/services/999999', ['name' => 'X'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Usluga nije pronađena.');
    }

    public function test_unknown_route_message_is_translated(): void
    {
        $this->withHeader('Accept-Language', 'sr')
            ->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('message', 'Traženi sadržaj nije pronađen.');
    }

    public function test_superadmin_forbidden_message_is_translated(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->withHeader('Accept-Language', 'sr')
            ->getJson('/api/superadmin/tenants')
            ->assertForbidden()
            ->assertJsonPath('message', 'Nemate dozvolu za ovu radnju.');
    }

    public function test_application_messages_are_not_replaced(): void
    {
        $this->withHeader('Accept-Language', 'sr')
            ->postJson('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Pogrešni podaci.');
    }

    // --- Stranica za upravljanje rezervacijom ---

    private function makeBooking(string $status): Booking
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => false]);

        return Booking::factory()->create(['slot_id' => $slot->id, 'status' => $status]);
    }

    #[DataProvider('localeProvider')]
    public function test_manage_page_is_translated(string $header, string $locale): void
    {
        $booking = $this->makeBooking('pending');

        $this->withHeader('Accept-Language', $header)
            ->get("/manage/{$booking->token}")
            ->assertOk()
            ->assertSee('lang="'.$locale.'"', false)
            ->assertSee(__('manage.title', [], $locale))
            ->assertSee(__('manage.status.pending', [], $locale))
            ->assertSee(__('manage.cancel_button', [], $locale));
    }

    #[DataProvider('localeProvider')]
    public function test_manage_page_shows_notice_for_cancelled_booking(string $header, string $locale): void
    {
        $booking = $this->makeBooking('cancelled');

        $this->withHeader('Accept-Language', $header)
            ->get("/manage/{$booking->token}")
            ->assertOk()
            ->assertSee(__('manage.status_notice.cancelled', [], $locale))
            ->assertDontSee(__('manage.cancel_button', [], $locale));
    }

    public function test_manage_cancel_flash_message_is_translated(): void
    {
        $booking = $this->makeBooking('pending');

        $this->withHeader('Accept-Language', 'sr')
            ->post("/manage/{$booking->token}/cancel")
            ->assertRedirect(route('booking.manage', $booking->token))
            ->assertSessionHas('success', 'Vaša rezervacija je uspešno otkazana.');
    }
}
