<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\Concerns\VerifiesCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'slot_id' => ['required', 'integer', 'exists:slots,id'],
            'start_time' => ['nullable', 'date_format:H:i'],
            // Rezervacija je uvek za uslugu kod radnika (radnik dolazi iz termina)
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.required' => __('messages.service_required'),
        ];
    }
}
