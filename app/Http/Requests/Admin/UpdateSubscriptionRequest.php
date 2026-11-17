<?php

namespace App\Http\Requests\Superadmin;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
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
            'subscription_status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'subscription_ends_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }
}
