<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slot_id' => $this->slot_id,
            'service_id' => $this->service_id,
            'tenant_id' => $this->tenant_id,
            'token' => $this->token,
            'status' => $this->status,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'note' => $this->note,
            'locale' => $this->locale,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'slot' => $this->whenLoaded('slot', fn () => SlotResource::make($this->slot)),
            'service' => $this->whenLoaded('service', fn () => ServiceResource::make($this->service)),
        ];
    }
}
