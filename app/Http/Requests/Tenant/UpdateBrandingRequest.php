<?php

namespace App\Http\Requests\Tenant;

use App\Enums\BusinessType;
use App\Enums\TenantTheme;
use App\Models\Tenant;
use App\Rules\UniqueBusinessName;
use App\Services\Contracts\CurrentTenantInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('updateBranding', Tenant::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Firma može da zadrži svoj naziv; tuđi (i isti u drugom obliku slova) nije dozvoljen
            'app_name' => ['sometimes', 'nullable', 'string', 'max:100', new UniqueBusinessName(app(CurrentTenantInterface::class)->get()?->id)],
            'primary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme' => ['sometimes', 'nullable', Rule::enum(TenantTheme::class)],
            'currency' => ['sometimes', Rule::in(Tenant::CURRENCIES)],
            'locale' => ['sometimes', Rule::in(Tenant::LOCALES)],
            'business_type' => ['sometimes', 'nullable', Rule::enum(BusinessType::class)],
        ];
    }
}
