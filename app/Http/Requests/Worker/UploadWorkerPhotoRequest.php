<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

class UploadWorkerPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('updateProfile', $this->route('worker'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:min_width=128,min_height=128'],
        ];
    }
}
