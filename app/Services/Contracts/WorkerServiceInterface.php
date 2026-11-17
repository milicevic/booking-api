<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface WorkerServiceInterface
{
    /**
     * @return Collection<int, User>
     */
    public function listFor(User $user, ?int $clientId = null): Collection;

    /**
     * @param  array{name: string, email?: ?string, phone?: ?string, bio?: ?string}  $data
     */
    public function create(User $client, array $data): User;

    /**
     * Novi ili promenjen email radnika koji još nema nalog šalje novu pozivnicu (stara prestaje da važi);
     * email se ne može obrisati radniku koji već ima nalog (422).
     *
     * @param  array{name?: string, email?: ?string, phone?: ?string, bio?: ?string, can_edit_slots?: bool}  $data
     */
    public function update(User $worker, array $data): User;

    public function delete(User $worker): void;
}
