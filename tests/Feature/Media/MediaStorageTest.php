<?php

namespace Tests\Feature\Media;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('public');
        Storage::fake('s3');
    }

    private function useBucket(): void
    {
        config(['filesystems.media_disk' => 's3']);
    }

    public function test_logo_goes_to_the_bucket_with_its_public_url(): void
    {
        $this->useBucket();
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $logoUrl = $this->actingAs($client)->postJson('/api/client/branding/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ])->assertOk()->json('logo_url');

        $path = collect(Storage::disk('s3')->allFiles("logos/{$this->tenant->id}"))->first();
        $this->assertNotNull($path);
        $this->assertSame(Storage::disk('s3')->url($path), $logoUrl);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_worker_photo_goes_to_the_bucket(): void
    {
        $this->useBucket();
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);

        $photoUrl = $this->actingAs($client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('marko.png', 300, 300),
        ])->assertOk()->json('worker_profile.photo_url');

        $path = collect(Storage::disk('s3')->allFiles("worker-photos/{$this->tenant->id}/{$worker->id}"))->first();
        $this->assertSame(Storage::disk('s3')->url($path), $photoUrl);
    }

    public function test_local_disk_keeps_relative_storage_urls(): void
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $logoUrl = $this->actingAs($client)->postJson('/api/client/branding/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ])->json('logo_url');

        $this->assertStringStartsWith("/storage/logos/{$this->tenant->id}/", $logoUrl);
    }

    public function test_existing_local_files_are_moved_to_the_bucket(): void
    {
        Storage::disk('public')->put('logos/1/old.webp', 'logo');
        Storage::disk('public')->put('worker-photos/1/2/old.webp', 'photo');
        $this->tenant->update(['logo_url' => '/storage/logos/1/old.webp']);
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $profile = WorkerProfile::factory()->create(['user_id' => $worker->id, 'tenant_id' => $this->tenant->id, 'photo_url' => '/storage/worker-photos/1/2/old.webp']);
        $this->useBucket();

        $this->artisan('media:move-to-disk')->expectsOutputToContain('Moved 2 file(s).')->assertSuccessful();

        Storage::disk('s3')->assertExists('logos/1/old.webp');
        Storage::disk('s3')->assertExists('worker-photos/1/2/old.webp');
        $this->assertSame(Storage::disk('s3')->url('logos/1/old.webp'), $this->tenant->fresh()->logo_url);
        $this->assertSame(Storage::disk('s3')->url('worker-photos/1/2/old.webp'), $profile->fresh()->photo_url);
    }

    public function test_missing_local_file_clears_the_url(): void
    {
        $this->tenant->update(['logo_url' => '/storage/logos/1/nema.webp']);
        $this->useBucket();

        $this->artisan('media:move-to-disk')->expectsOutputToContain('1 file(s) were missing')->assertSuccessful();

        $this->assertNull($this->tenant->fresh()->logo_url);
    }

    public function test_move_requires_a_bucket_disk(): void
    {
        $this->artisan('media:move-to-disk')->expectsOutputToContain('MEDIA_DISK is "public"')->assertFailed();
    }
}
