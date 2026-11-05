<?php

namespace App\Http\Requests\Slot;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSlotRequest extends FormRequest
{
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
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function worker(): User
    {
        return User::findOrFail($this->integer('worker_id'));
    }

    protected function passedValidation(): void
    {
        Gate::forUser($this->user())->authorize('create', [Slot::class, $this->worker()]);
    }

    /**
     * @return array{worker_id: int, date: string, start_time: string, end_time: string}
     */
    public function slotData(): array
    {
        /** @var array{worker_id: int, date: string, start_time: string, end_time: string} $data */
        $data = $this->validated();

        return $data;
    }
}
