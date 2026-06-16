<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Override Sanctum's tokenable lookup to bypass global scopes.
     * Ensures platform-level users (admin, superadmin) with null tenant_id
     * are resolved even when a tenant context is active.
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo('tokenable')->withoutGlobalScopes();
    }
}
