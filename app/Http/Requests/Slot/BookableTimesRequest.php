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
            // Filter prikaza: bez radnika vraćaju se vremena svih radnika usluge (rezervacija uvek ide preko slot_id radnika)
            'worker_id' => ['nullable', 'integer', 'exists:users,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date' => ['nullable', 'date'],
        ];
    }
}
