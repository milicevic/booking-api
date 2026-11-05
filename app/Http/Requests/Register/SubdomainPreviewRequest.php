<?php

namespace App\Http\Requests\Register;

use Illuminate\Foundation\Http\FormRequest;

class SubdomainPreviewRequest extends FormRequest
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
            'app_name' => ['required', 'string', 'max:255'],
        ];
    }
}
