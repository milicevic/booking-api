<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Javna podešavanja sajta firme (izgled, valuta, jezik, pretplata).
 *
 * @mixin Tenant
 */
class TenantConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app_name' => $this->app_name,
            'logo_url' => $this->logo_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'theme' => $this->theme,
            'subdomain' => $this->subdomain,
            'currency' => $this->currency,
            'locale' => $this->locale,
            'subscription_status' => $this->subscription_status,
            'trial_ends_at' => $this->trial_ends_at,
            'subscription_ends_at' => $this->subscription_ends_at,
        ];
    }
}
