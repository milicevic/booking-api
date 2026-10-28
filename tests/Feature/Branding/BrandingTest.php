<?php

namespace Tests\Feature\Branding;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    // --- updateBranding ---

    public function test_client_can_update_branding(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($client)->patchJson('/api/client/branding', [
            'app_name' => 'My Salon',
            'primary_color' => '#FF5733',
            'secondary_color' => '#33FF57',
            'theme' => 'modern',
        ]);

        $response->assertOk()
            ->assertJsonPath('app_name', 'My Salon')
            ->assertJsonPath('primary_color', '#FF5733')
            ->assertJsonPath('secondary_color', '#33FF57')
            ->assertJsonPath('theme', 'modern');

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'app_name' => 'My Salon',
            'theme' => 'modern',
        ]);
    }

    public function test_client_can_update_partial_branding(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($client)->patchJson('/api/client/branding', [
            'app_name' => 'New Name',
        ]);

        $response->assertOk()->assertJsonPath('app_name', 'New Name');
    }

    public function test_update_branding_returns_422_for_invalid_hex_color(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)->patchJson('/api/client/branding', [
            'primary_color' => 'not-a-color',
        ])->assertUnprocessable()->assertJsonValidationErrors(['primary_color']);
    }

    public function test_update_branding_returns_422_for_invalid_theme(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)->patchJson('/api/client/branding', [
            'theme' => 'invalid-theme',
        ])->assertUnprocessable()->assertJsonValidationErrors(['theme']);
    }

    public function test_worker_cannot_update_branding(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($worker)->patchJson('/api/client/branding', [
            'app_name' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_update_branding_requires_auth(): void
    {
        $this->patchJson('/api/client/branding', ['app_name' => 'Test'])->assertUnauthorized();
    }

    // --- uploadLogo ---

    public function test_client_can_upload_logo(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.png', 256, 256);

        $response = $this->actingAs($client)->postJson('/api/client/branding/logo', [
            'logo' => $file,
        ]);

        $response->assertOk()->assertJsonStructure(['logo_url']);

        $logoUrl = $response->json('logo_url');
        $this->assertNotNull($logoUrl);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id, 'logo_url' => $logoUrl]);
    }

    public function test_uploaded_logo_is_stored_as_webp(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.png', 256, 256);

        $this->actingAs($client)->postJson('/api/client/branding/logo', ['logo' => $file])->assertOk();

        $files = Storage::disk('public')->files("logos/{$this->tenant->id}");
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.webp', $files[0]);
        $this->assertSame('image/webp', getimagesizefromstring(Storage::disk('public')->get($files[0]))['mime']);
    }

    public function test_large_logo_is_scaled_down_keeping_aspect_ratio(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.jpg', 2000, 1000);

        $this->actingAs($client)->postJson('/api/client/branding/logo', ['logo' => $file])->assertOk();

        $path = Storage::disk('public')->files("logos/{$this->tenant->id}")[0];
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([512, 256], [$width, $height]);
    }

    public function test_small_logo_is_not_upscaled(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.png', 200, 150);

        $this->actingAs($client)->postJson('/api/client/branding/logo', ['logo' => $file])->assertOk();

        $path = Storage::disk('public')->files("logos/{$this->tenant->id}")[0];
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([200, 150], [$width, $height]);
    }

    public function test_upload_logo_rejects_too_small_image(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.png', 64, 64);

        $this->actingAs($client)->postJson('/api/client/branding/logo', ['logo' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);
    }

    public function test_upload_logo_rejects_svg(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><script>alert(1)</script></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svg);

        $this->actingAs($client)->postJson('/api/client/branding/logo', ['logo' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);
    }

    public function test_upload_logo_rejects_gif(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.gif', 256, 256);

        $this->actingAs($client)->postJson('/api/client/branding/logo', ['logo' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);
    }

    public function test_upload_logo_rejects_non_image(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($client)->postJson('/api/client/branding/logo', [
            'logo' => $file,
        ])->assertUnprocessable()->assertJsonValidationErrors(['logo']);
    }

    public function test_upload_logo_requires_file(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)->postJson('/api/client/branding/logo', [])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);
    }

    public function test_worker_cannot_upload_logo(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->image('logo.png', 256, 256);

        $this->actingAs($worker)->postJson('/api/client/branding/logo', [
            'logo' => $file,
        ])->assertForbidden();
    }

    public function test_upload_logo_requires_auth(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 256, 256);
        $this->postJson('/api/client/branding/logo', ['logo' => $file])->assertUnauthorized();
    }

    public function test_client_can_set_currency_and_notification_language(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->patchJson('/api/client/branding', ['currency' => 'EUR', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('currency', 'EUR')
            ->assertJsonPath('locale', 'en');

        $this->getJson('/api/tenant/config')
            ->assertJsonPath('currency', 'EUR')
            ->assertJsonPath('locale', 'en');
    }

    public function test_currency_and_language_must_be_supported(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($client)
            ->patchJson('/api/client/branding', ['currency' => 'XYZ', 'locale' => 'de'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['currency', 'locale']);
    }

    public function test_new_tenant_defaults_to_rsd(): void
    {
        $this->assertSame('RSD', $this->tenant->fresh()->currency);
    }

    public function test_config_includes_trial_end_date(): void
    {
        $this->tenant->update(['subscription_status' => 'trialing', 'trial_ends_at' => now()->addDays(5)]);

        $this->getJson('/api/tenant/config')
            ->assertOk()
            ->assertJsonPath('subscription_status', 'trialing')
            ->assertJsonPath('trial_ends_at', $this->tenant->fresh()->trial_ends_at->toJSON());
    }
}
