<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
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
            'logo' => ['required', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=128,min_height=128'],
        ];
    }
}
