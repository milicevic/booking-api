<?php

namespace App\Http\Resources;

use App\Models\ClientProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClientProfile
 */
class ClientProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'phone' => $this->phone,
            'business_name' => $this->business_name,
            'auto_confirm_bookings' => $this->auto_confirm_bookings,
            'customer_contact' => $this->customer_contact,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
