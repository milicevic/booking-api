<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Contracts\CurrentTenantInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * @implements Scope<Model>
 */
class TenantScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Admin upravlja svim firmama, pa ga tenant filter ne ograničava
        if ($user?->role === UserRole::Admin) {
            return;
        }

        $tenant = app(CurrentTenantInterface::class)->get();

        if (! $tenant) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenant->id);
    }
}
