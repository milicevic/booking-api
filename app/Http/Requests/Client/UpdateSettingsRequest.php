<?php

namespace App\Http\Requests\Client;

use App\Enums\ConfirmationMode;
use App\Enums\CustomerContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
            'auto_confirm_bookings' => ['sometimes', 'boolean'],
            'customer_contact' => ['sometimes', Rule::enum(CustomerContact::class)],
            'confirmation_mode' => ['sometimes', Rule::enum(ConfirmationMode::class)],
            'onboarding_completed' => ['sometimes', 'boolean'],
            'reschedule_cutoff_hours' => ['sometimes', 'integer', 'min:0', 'max:720'],
            'calendar_invites' => ['sometimes', 'boolean'],
            // Zaštita od zauzimanja termina; 0 = bez ograničenja
            'max_active_bookings_per_customer' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'max_daily_bookings_per_customer' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'pending_expiry_hours' => ['sometimes', 'integer', 'min:0', 'max:720'],
        ];
    }

    /**
     * Polja za client_profiles; onboarding_completed (true/false) postaje vreme završetka ili null.
     *
     * @return array{auto_confirm_bookings?: bool, customer_contact?: string, confirmation_mode?: string, reschedule_cutoff_hours?: int, calendar_invites?: bool, max_active_bookings_per_customer?: int, max_daily_bookings_per_customer?: int, pending_expiry_hours?: int, onboarding_completed_at?: ?Carbon}
     */
    public function settings(): array
    {
        /** @var array{auto_confirm_bookings?: bool, customer_contact?: string, confirmation_mode?: string, reschedule_cutoff_hours?: int, calendar_invites?: bool, max_active_bookings_per_customer?: int, max_daily_bookings_per_customer?: int, pending_expiry_hours?: int, onboarding_completed?: bool} $data */
        $data = $this->validated();

        if (array_key_exists('onboarding_completed', $data)) {
            $data['onboarding_completed_at'] = $data['onboarding_completed'] ? now() : null;
            unset($data['onboarding_completed']);
        }

        return $data;
    }
}
