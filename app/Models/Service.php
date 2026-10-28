<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'duration_minutes',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'worker_services', 'service_id', 'worker_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Naziv usluge sa cenom u valuti firme, npr. "Šišanje (1.200 RSD)".
     */
    public function labelWithPrice(): string
    {
        if ($this->price === null) {
            return $this->name;
        }

        $price = (float) $this->price;
        $decimals = floor($price) == $price ? 0 : 2;
        [$decimalSeparator, $thousandsSeparator] = app()->getLocale() === 'sr' ? [',', '.'] : ['.', ','];
        $currency = $this->tenant?->currency ?? 'RSD';

        return "{$this->name} (".number_format($price, $decimals, $decimalSeparator, $thousandsSeparator)." {$currency})";
    }
}
