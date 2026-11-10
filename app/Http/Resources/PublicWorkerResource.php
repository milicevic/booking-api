<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Radnik kako ga vidi kupac na sajtu firme: bez emaila, telefona i ostalih podataka naloga.
 *
 * @mixin User
 */
class PublicWorkerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo_url' => $this->workerProfile?->photo_url,
            'bio' => $this->workerProfile?->bio,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
