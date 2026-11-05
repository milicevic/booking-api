<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantBrandingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app_name' => $this->app_name,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'theme' => $this->theme,
            'logo_url' => $this->logo_url,
            'currency' => $this->currency,
            'locale' => $this->locale,
        ];
    }
}
