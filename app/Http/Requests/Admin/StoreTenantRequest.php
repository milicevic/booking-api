<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use App\Rules\UniqueBusinessName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
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
            'app_name' => ['required', 'string', 'max:255', new UniqueBusinessName],
            'subdomain' => ['nullable', 'string', 'alpha_dash', 'max:63', 'unique:tenants,subdomain', Rule::notIn(Tenant::RESERVED_SUBDOMAINS)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }
}
