<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && $user->role === 'superadmin') {
            return;
        }

        if (! app()->bound('current_tenant')) {
            return;
        }

        $tenant = app('current_tenant');

        $builder->where($model->getTable().'.tenant_id', $tenant->id);
    }
}
