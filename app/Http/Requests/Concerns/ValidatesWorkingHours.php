<?php

namespace App\Http\Requests\Concerns;

/**
 * Pravila za radne dane, radno vreme, dužinu termina i pauze (grupno pravljenje termina i raspored radnika).
 */
trait ValidatesWorkingHours
{
    /**
     * @return array<string, mixed>
     */
    protected function workingHoursRules(): array
    {
        return [
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
            'slot_duration' => ['required', 'integer', 'min:5', 'max:720'],
            'breaks' => ['sometimes', 'array'],
            'breaks.*.start' => ['required', 'date_format:H:i'],
            'breaks.*.end' => ['required', 'date_format:H:i', 'after:breaks.*.start'],
        ];
    }
}
