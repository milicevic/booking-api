<?php

namespace App\Services\Contracts;

interface SubdomainGeneratorInterface
{
    /** Slobodan poddomen od naziva firme (npr. "Moj Salon" → "moj-salon", "moj-salon-1"...). */
    public function uniqueFor(string $appName): string;
}
