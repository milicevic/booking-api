<?php

namespace App\Http\Requests\Slot;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filteri za listu termina (kalendar). Bez per_page vraća se ceo niz, kao ranije.
 */
class ListSlotsRequest extends FormRequest
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
            'client_id' => ['sometimes', 'integer'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'worker_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }

    /**
     * @return array{from?: ?string, to?: ?string, worker_id?: ?int}
     */
    public function filters(): array
    {
        return [
            'from' => $this->input('from'),
            'to' => $this->input('to'),
            'worker_id' => $this->filled('worker_id') ? $this->integer('worker_id') : null,
        ];
    }

    public function perPage(): ?int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : null;
    }
}
