<?php

namespace App\Http\Requests\Superadmin;

use App\Enums\TenantTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', Rule::enum(TenantTheme::class)],
        ];
    }
}
