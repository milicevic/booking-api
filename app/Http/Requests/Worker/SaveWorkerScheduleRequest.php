<?php

namespace App\Http\Requests\Worker;

use App\Http\Requests\Concerns\ValidatesWorkingHours;
use Illuminate\Foundation\Http\FormRequest;

class SaveWorkerScheduleRequest extends FormRequest
{
    use ValidatesWorkingHours;

    /** Autorizacija u kontroleru (ista pravila kao pravljenje termina, SlotPolicy::create). */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->workingHoursRules();
    }

    /**
     * @return array{weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>}
     */
    public function scheduleData(): array
    {
        /** @var array{weekdays: list<int>, start: string, end: string, slot_duration: int, breaks?: list<array{start: string, end: string}>} $data */
        $data = $this->validated();

        return $data;
    }
}
