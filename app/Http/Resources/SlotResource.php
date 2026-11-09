<?php

namespace App\Http\Resources;

use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Slot
 */
class SlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'tenant_id' => $this->tenant_id,
            // Samo datum (Y-m-d): kao trenutak u UTC bi ponoć po lokalnom vremenu postala prethodni dan
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'worker' => $this->whenLoaded('worker', fn () => UserResource::make($this->worker)),
            'booking' => $this->whenLoaded('booking', fn () => BookingResource::make($this->booking)),
        ];
    }
}
