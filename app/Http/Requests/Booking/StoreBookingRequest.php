<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'slot_id' => ['required', 'integer', 'exists:slots,id'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ];
    }
}
