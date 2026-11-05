<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Korisnik bez osetljivih polja (lozinka, google_id, invite_token).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'client_id' => $this->client_id,
            'tenant_id' => $this->tenant_id,
            'can_edit_slots' => $this->can_edit_slots,
            'is_suspended' => $this->is_suspended,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'client_profile' => $this->whenLoaded('clientProfile', fn () => ClientProfileResource::make($this->clientProfile)),
            'worker_profile' => $this->whenLoaded('workerProfile', fn () => WorkerProfileResource::make($this->workerProfile)),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'workers_count' => $this->whenCounted('workers'),
        ];
    }
}
