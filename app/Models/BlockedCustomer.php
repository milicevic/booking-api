<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Kupac koga je firma blokirala: nove rezervacije sa ovim telefonom ili emailom se odbijaju.
 *
 * @property int $id
 * @property ?int $tenant_id
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $name
 * @property ?int $blocked_by
 * @property Carbon $created_at
 */
class BlockedCustomer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'phone',
        'email',
        'name',
        'blocked_by',
    ];
}
