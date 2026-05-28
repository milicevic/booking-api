<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (self $model) {
            if (app()->bound('current_tenant') && is_null($model->tenant_id)) {
                $model->tenant_id = app('current_tenant')->id;
            }
        });
    }
}
