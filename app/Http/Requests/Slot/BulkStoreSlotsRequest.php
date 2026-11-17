<?php

namespace App\Http\Requests\Slot;

use App\Http\Requests\Concerns\ValidatesWorkingHours;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Grupno pravljenje termina za period: radni dani, radno vreme, dužina termina i pauze.
 */
class BulkStoreSlotsRequest extends FormRequest
{
    use ValidatesWorkingHours;

    /** Autorizacija zavisi od radnika iz zahteva, pa se proverava tek posle validacije. */
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
            'date_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            ...$this->workingHoursRules(),
        ];
    }

    protected function passedValidation(): void
    {
        Gate::forUser($this->user())->authorize('create', [Slot::class, User::findOrFail($this->integer('worker_id'))]);
    }

    /**
     * @return array{worker_id: int, date_from: string, date_to: string, weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>}
     */
    public function bulkData(): array
    {
        /** @var array{worker_id: int, date_from: string, date_to: string, weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>} $data */
        $data = $this->validated();

        return $data;
    }
}
