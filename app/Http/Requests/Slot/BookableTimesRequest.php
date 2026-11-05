<?php

namespace App\Http\Requests\Slot;

use Illuminate\Foundation\Http\FormRequest;

class BookableTimesRequest extends FormRequest
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
            'worker_id' => ['required', 'integer', 'exists:users,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'date' => ['nullable', 'date'],
        ];
    }
}
