<?php

namespace App\Http\Requests\Register;

use App\Http\Requests\Concerns\VerifiesCaptcha;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    use VerifiesCaptcha;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'app_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['nullable', 'string', 'alpha_dash', 'max:63', 'unique:tenants,subdomain', Rule::notIn(Tenant::RESERVED_SUBDOMAINS)],
        ];
    }
}
