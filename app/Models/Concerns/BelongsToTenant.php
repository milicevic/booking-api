<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Services\Contracts\CurrentTenantInterface;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model): void {
            $tenant = app(CurrentTenantInterface::class)->get();

            if ($tenant && is_null($model->tenant_id)) {
                $model->tenant_id = $tenant->id;
            }
        });
    }
}
