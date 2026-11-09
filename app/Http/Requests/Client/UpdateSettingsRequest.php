<?php

namespace App\Http\Requests\Client;

use App\Enums\CustomerContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
            'auto_confirm_bookings' => ['sometimes', 'boolean'],
            'customer_contact' => ['sometimes', Rule::enum(CustomerContact::class)],
        ];
    }
}
