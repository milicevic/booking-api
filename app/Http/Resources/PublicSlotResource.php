<?php

namespace App\Http\Resources;

use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slobodan termin na javnom sajtu firme, sa javnim podacima o radniku.
 *
 * @mixin Slot
 */
class PublicSlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            // Samo datum (Y-m-d): kao trenutak u UTC bi ponoć po lokalnom vremenu postala prethodni dan
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_available' => $this->is_available,
            'worker' => $this->whenLoaded('worker', fn () => PublicWorkerResource::make($this->worker)),
        ];
    }
}
