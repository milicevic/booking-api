<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum TenantTheme: string
{
    use HasValues;

    case Minimal = 'minimal';
    case Modern = 'modern';
    case Classic = 'classic';
}
