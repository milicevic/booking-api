<?php

namespace App\Http\Resources;

use App\Enums\ConfirmationMode;
use App\Enums\CustomerContact;
use App\Models\ClientProfile;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Javna podešavanja sajta firme (izgled, valuta, jezik, pretplata).
 *
 * @mixin Tenant
 */
class TenantConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app_name' => $this->app_name,
            'business_type' => $this->business_type,
            'logo_url' => $this->logo_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'theme' => $this->theme,
            'subdomain' => $this->subdomain,
            'currency' => $this->currency,
            'locale' => $this->locale,
            // Koje kontakt podatke kupac mora da ostavi (podešavanje vlasnika firme)
            'customer_contact' => $this->clientProfiles->first()->customer_contact ?? CustomerContact::EmailOrPhone,
            // Do koliko sati pre početka kupac sme sam da pomeri rezervaciju
            'reschedule_cutoff_hours' => $this->clientProfiles->first()->reschedule_cutoff_hours ?? ClientProfile::DEFAULT_RESCHEDULE_CUTOFF_HOURS,
            // Ko potvrđuje rezervacije; radnik po tome zna da li sme sam da potvrdi svoje termine
            'confirmation_mode' => $this->clientProfiles->first()->confirmation_mode ?? ConfirmationMode::Owner,
            'subscription_status' => $this->subscription_status,
            'trial_ends_at' => $this->trial_ends_at,
            'subscription_ends_at' => $this->subscription_ends_at,
        ];
    }
}
