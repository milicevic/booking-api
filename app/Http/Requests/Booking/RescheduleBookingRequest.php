<?php

namespace App\Http\Requests\Booking;

use App\Models\Slot;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    /**
     * Isti zahtev za pomeranje od strane vlasnika/radnika (autorizacija u kontroleru, BookingPolicy::reschedule)
     * i od strane kupca (javno, po tokenu).
     */
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
            'slot_id' => ['required', 'integer'],
            'start_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function targetSlot(): Slot
    {
        return Slot::with('worker')->findOrFail($this->integer('slot_id'));
    }
}
