<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingLinkAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Potvrda ili odbijanje rezervacije preko potpisanog linka iz mejla (bez prijave).
 */
class ConfirmByLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->hasValidSignature();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(BookingLinkAction::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => __('messages.invalid_action'),
            'action.enum' => __('messages.invalid_action'),
        ];
    }

    public function action(): BookingLinkAction
    {
        return BookingLinkAction::from($this->string('action')->toString());
    }

    protected function failedAuthorization(): never
    {
        throw new AuthorizationException(__('messages.invalid_link'));
    }
}
