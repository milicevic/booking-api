<?php

namespace Tests\Feature\Worker;

use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('public');
        $this->client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
    }

    private function worker(): User
    {
        $worker = User::factory()->worker($this->client->id)->create(['tenant_id' => $this->tenant->id]);
        WorkerProfile::factory()->create(['user_id' => $worker->id, 'tenant_id' => $this->tenant->id, 'phone' => '0601234567']);

        return $worker;
    }

    private function storedPath(?string $photoUrl): string
    {
        return str_replace('/storage/', '', (string) $photoUrl);
    }

    public function test_worker_can_be_created_with_a_bio(): void
    {
        $this->actingAs($this->client)->postJson('/api/workers', ['name' => 'Marko', 'bio' => 'Majstor za muško šišanje.'])
            ->assertCreated()
            ->assertJsonPath('worker_profile.bio', 'Majstor za muško šišanje.');
    }

    public function test_bio_can_be_updated_without_touching_the_phone(): void
    {
        $worker = $this->worker();

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['bio' => 'Novi opis'])
            ->assertOk()
            ->assertJsonPath('worker_profile.bio', 'Novi opis')
            ->assertJsonPath('worker_profile.phone', '0601234567');
    }

    public function test_bio_is_limited_in_length(): void
    {
        $worker = $this->worker();

        $this->actingAs($this->client)->putJson("/api/workers/{$worker->id}", ['bio' => str_repeat('a', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bio']);
    }

    public function test_photo_is_stored_as_square_webp(): void
    {
        $worker = $this->worker();

        $response = $this->actingAs($this->client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('marko.jpg', 800, 600),
        ])->assertOk();

        $photoUrl = $response->json('worker_profile.photo_url');
        $this->assertStringStartsWith("/storage/worker-photos/{$this->tenant->id}/{$worker->id}/", $photoUrl);
        $this->assertStringEndsWith('.webp', $photoUrl);
        Storage::disk('public')->assertExists($this->storedPath($photoUrl));

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($this->storedPath($photoUrl)));
        $this->assertSame([384, 384], [$width, $height]);
    }

    public function test_new_photo_replaces_the_old_one(): void
    {
        $worker = $this->worker();
        $upload = fn () => $this->actingAs($this->client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('marko.png', 300, 300),
        ])->json('worker_profile.photo_url');

        $first = $upload();
        $second = $upload();

        Storage::disk('public')->assertMissing($this->storedPath($first));
        Storage::disk('public')->assertExists($this->storedPath($second));
    }

    public function test_photo_can_be_removed(): void
    {
        $worker = $this->worker();
        $photoUrl = $this->actingAs($this->client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('marko.png', 300, 300),
        ])->json('worker_profile.photo_url');

        $this->actingAs($this->client)->deleteJson("/api/workers/{$worker->id}/photo")
            ->assertOk()
            ->assertJsonPath('worker_profile.photo_url', null);

        Storage::disk('public')->assertMissing($this->storedPath($photoUrl));
    }

    public function test_photo_must_be_an_image_of_reasonable_size(): void
    {
        $worker = $this->worker();

        $this->actingAs($this->client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('mala.png', 64, 64),
        ])->assertUnprocessable()->assertJsonValidationErrors(['photo']);

        $this->actingAs($this->client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['photo']);
    }

    public function test_client_cannot_change_another_clients_worker_photo(): void
    {
        $otherClient = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $otherWorker = User::factory()->worker($otherClient->id)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->client)->postJson("/api/workers/{$otherWorker->id}/photo", [
            'photo' => UploadedFile::fake()->image('x.png', 300, 300),
        ])->assertForbidden();

        $this->actingAs($this->client)->deleteJson("/api/workers/{$otherWorker->id}/photo")->assertForbidden();
    }

    public function test_deleting_a_worker_removes_the_photo(): void
    {
        $worker = $this->worker();
        $photoUrl = $this->actingAs($this->client)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('marko.png', 300, 300),
        ])->json('worker_profile.photo_url');

        $this->actingAs($this->client)->deleteJson("/api/workers/{$worker->id}")->assertNoContent();

        Storage::disk('public')->assertMissing($this->storedPath($photoUrl));
    }

    public function test_public_slots_show_photo_and_bio_but_not_contact_details(): void
    {
        $worker = $this->worker();
        $worker->workerProfile->update(['photo_url' => '/storage/worker-photos/x.webp', 'bio' => 'Majstor']);
        $service = Service::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $worker->services()->attach($service->id);
        Slot::factory()->create(['worker_id' => $worker->id, 'date' => today()->addDay(), 'start_time' => '09:00:00', 'end_time' => '09:30:00', 'is_available' => true]);

        $this->getJson('/api/slots/available')
            ->assertOk()
            ->assertJsonPath('0.worker.name', $worker->name)
            ->assertJsonPath('0.worker.photo_url', '/storage/worker-photos/x.webp')
            ->assertJsonPath('0.worker.bio', 'Majstor')
            ->assertJsonPath('0.worker.services.0.id', $service->id)
            ->assertJsonMissingPath('0.worker.email')
            ->assertJsonMissingPath('0.worker.worker_profile')
            ->assertJsonMissingPath('0.worker.role');
    }

    public function test_worker_can_edit_own_bio_and_photo(): void
    {
        $worker = $this->worker();

        $this->actingAs($worker)->getJson("/api/workers/{$worker->id}")->assertOk();

        $this->actingAs($worker)->patchJson("/api/workers/{$worker->id}/profile", ['bio' => 'Moj opis'])
            ->assertOk()
            ->assertJsonPath('worker_profile.bio', 'Moj opis');

        $this->actingAs($worker)->postJson("/api/workers/{$worker->id}/photo", [
            'photo' => UploadedFile::fake()->image('ja.png', 300, 300),
        ])->assertOk();

        $this->actingAs($worker)->deleteJson("/api/workers/{$worker->id}/photo")->assertOk();
    }

    public function test_client_can_edit_bio_through_profile_endpoint(): void
    {
        $worker = $this->worker();

        $this->actingAs($this->client)->patchJson("/api/workers/{$worker->id}/profile", ['bio' => null])
            ->assertOk()
            ->assertJsonPath('worker_profile.bio', null);
    }

    public function test_worker_cannot_edit_a_colleagues_profile_or_own_account(): void
    {
        $worker = $this->worker();
        $colleague = $this->worker();

        $this->actingAs($worker)->patchJson("/api/workers/{$colleague->id}/profile", ['bio' => 'x'])->assertForbidden();
        $this->actingAs($worker)->postJson("/api/workers/{$colleague->id}/photo", [
            'photo' => UploadedFile::fake()->image('x.png', 300, 300),
        ])->assertForbidden();
        $this->actingAs($worker)->getJson("/api/workers/{$colleague->id}")->assertForbidden();

        // Ime, email i dozvole i dalje menja samo klijent
        $this->actingAs($worker)->putJson("/api/workers/{$worker->id}", ['name' => 'Drugo ime', 'can_edit_slots' => true])->assertForbidden();
    }
}
