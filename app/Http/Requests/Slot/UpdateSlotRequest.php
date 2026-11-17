<?php

namespace App\Http\Requests\Slot;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlotRequest extends FormRequest
{
    /** Autorizacija u kontroleru (SlotPolicy::update), jer zavisi od termina iz rute. */
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
            'is_blocked' => ['required', 'boolean'],
        ];
    }
}
