<?php

namespace App\Http\Requests\Push;

use Illuminate\Foundation\Http\FormRequest;

class UnsubscribePushRequest extends FormRequest
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
            'endpoint' => ['required', 'string'],
        ];
    }
}
