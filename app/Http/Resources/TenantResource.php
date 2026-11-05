<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'app_name' => $this->app_name,
            'subdomain' => $this->subdomain,
            'custom_domain' => $this->custom_domain,
            'logo_url' => $this->logo_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'theme' => $this->theme,
            'currency' => $this->currency,
            'locale' => $this->locale,
            'subscription_status' => $this->subscription_status,
            'trial_ends_at' => $this->trial_ends_at,
            'subscription_ends_at' => $this->subscription_ends_at,
            'deploy_status' => $this->deploy_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
