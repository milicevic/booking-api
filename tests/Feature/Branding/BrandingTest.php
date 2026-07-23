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
        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($client)->postJson('/api/client/branding/logo', [
            'logo' => $file,
        ]);

        $response->assertOk()->assertJsonStructure(['logo_url']);

        $logoUrl = $response->json('logo_url');
        $this->assertNotNull($logoUrl);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id, 'logo_url' => $logoUrl]);
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
        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $this->actingAs($worker)->postJson('/api/client/branding/logo', [
            'logo' => $file,
        ])->assertForbidden();
    }

    public function test_upload_logo_requires_auth(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');
        $this->postJson('/api/client/branding/logo', ['logo' => $file])->assertUnauthorized();
    }
}
