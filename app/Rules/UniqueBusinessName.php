<?php

namespace App\Rules;

use App\Models\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Naziv firme mora biti jedinstven na platformi, bez obzira na velika i mala slova i razmake na krajevima.
 */
class UniqueBusinessName implements ValidationRule
{
    /**
     * @param  ?int  $ignoreTenantId  firma koja menja svoj naziv (njen trenutni naziv nije duplikat)
     */
    public function __construct(private readonly ?int $ignoreTenantId = null) {}

    public static function isTaken(string $name, ?int $ignoreTenantId = null): bool
    {
        return Tenant::query()
            ->whereRaw('lower(trim(app_name)) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreTenantId, fn ($query) => $query->whereKeyNot($ignoreTenantId))
            ->exists();
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && trim($value) !== '' && self::isTaken($value, $this->ignoreTenantId)) {
            $fail(__('messages.business_name_taken'));
        }
    }
}
