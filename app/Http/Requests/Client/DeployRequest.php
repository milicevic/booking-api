<?php

namespace App\Http\Requests\Client;

use App\Models\Tenant;
use App\Services\Contracts\CurrentTenantInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeployRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('requestDeploy', Tenant::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subdomain' => ['nullable', 'string', 'alpha_dash', 'max:63', Rule::unique('tenants', 'subdomain')->ignore($this->tenantId()), Rule::notIn(Tenant::RESERVED_SUBDOMAINS)],
            'custom_domain' => ['nullable', 'string', 'max:253'],
        ];
    }

    private function tenantId(): ?int
    {
        return app(CurrentTenantInterface::class)->get()?->id;
    }
}
