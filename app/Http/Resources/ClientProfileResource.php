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
            'confirmation_mode' => $this->confirmation_mode,
            'reschedule_cutoff_hours' => $this->reschedule_cutoff_hours,
            'calendar_invites' => $this->calendar_invites,
            'max_active_bookings_per_customer' => $this->max_active_bookings_per_customer,
            'max_daily_bookings_per_customer' => $this->max_daily_bookings_per_customer,
            'pending_expiry_hours' => $this->pending_expiry_hours,
            'onboarding_completed_at' => $this->onboarding_completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
