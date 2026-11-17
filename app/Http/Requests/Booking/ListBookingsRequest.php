<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filteri za listu rezervacija (opseg datuma termina, radnik, status). Bez per_page vraća se ceo niz, kao ranije.
 */
class ListBookingsRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(BookingStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }

    /**
     * @return array{from?: ?string, to?: ?string, worker_id?: ?int, status?: ?string}
     */
    public function filters(): array
    {
        return [
            'from' => $this->input('from'),
            'to' => $this->input('to'),
            'worker_id' => $this->filled('worker_id') ? $this->integer('worker_id') : null,
            'status' => $this->input('status'),
        ];
    }

    public function perPage(): ?int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : null;
    }
}
