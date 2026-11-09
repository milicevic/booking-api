<?php

namespace App\Services\Worker;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\WorkerInvite;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\WorkerRepositoryInterface;
use App\Services\Contracts\CurrentTenantInterface;
use App\Services\Contracts\WorkerPhotoServiceInterface;
use App\Services\Contracts\WorkerServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class WorkerService implements WorkerServiceInterface
{
    public function __construct(
        private readonly WorkerRepositoryInterface $workers,
        private readonly ServiceRepositoryInterface $services,
        private readonly CurrentTenantInterface $currentTenant,
        private readonly WorkerPhotoServiceInterface $photos,
    ) {}

    public function listFor(User $user, ?int $clientId = null): Collection
    {
        $ownerId = $user->role === UserRole::Admin && $clientId !== null ? $clientId : $user->id;

        return $this->workers->workersOfClient($ownerId);
    }

    public function create(User $client, array $data): User
    {
        $email = $data['email'] ?? null;
        $inviteToken = $email ? Str::uuid()->toString() : null;

        $worker = $this->workers->createWithProfile([
            'name' => $data['name'],
            'email' => $email,
            'client_id' => $client->id,
            'invite_token' => $inviteToken,
        ], [
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);

        // Novi radnik podrazumevano radi sve aktivne usluge firme
        $worker->services()->sync($this->services->activeIds());

        if ($inviteToken) {
            $worker->notify(new WorkerInvite($this->currentTenant->require(), $inviteToken));
        }

        return $worker->load('workerProfile');
    }

    public function update(User $worker, array $data): User
    {
        $worker->update(array_filter([
            'name' => $data['name'] ?? null,
            'email' => array_key_exists('email', $data) ? $data['email'] : $worker->email,
            'can_edit_slots' => $data['can_edit_slots'] ?? null,
        ], fn (mixed $value): bool => $value !== null));

        $profile = array_intersect_key($data, array_flip(['phone', 'bio']));

        if ($profile !== []) {
            $worker->workerProfile()->updateOrCreate(['user_id' => $worker->id], $profile);
        }

        return ($worker->fresh() ?? $worker)->load('workerProfile');
    }

    public function delete(User $worker): void
    {
        $this->photos->delete($worker);
        $worker->delete();
    }
}
