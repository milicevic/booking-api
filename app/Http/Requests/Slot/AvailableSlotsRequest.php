<?php

namespace App\Http\Requests\Slot;

use Illuminate\Foundation\Http\FormRequest;

class AvailableSlotsRequest extends FormRequest
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
            'date' => ['sometimes', 'date'],
            'worker_id' => ['sometimes', 'exists:users,id'],
            'service_id' => ['sometimes', 'exists:services,id'],
        ];
    }
}
