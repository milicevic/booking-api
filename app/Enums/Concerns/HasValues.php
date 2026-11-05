<?php

namespace App\Enums\Concerns;

/**
 * Lista string vrednosti backed enuma, npr. za pravilo validacije Rule::in(Enum::values()).
 */
trait HasValues
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
