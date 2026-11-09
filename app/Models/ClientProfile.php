<?php

namespace App\Models;

use App\Enums\CustomerContact;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClientProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property ?int $tenant_id
 * @property ?string $phone
 * @property ?string $business_name
 * @property bool $auto_confirm_bookings
 */
class ClientProfile extends Model
{
    /** @use HasFactory<ClientProfileFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'phone',
        'business_name',
        'auto_confirm_bookings',
        'customer_contact',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_confirm_bookings' => 'boolean',
            'customer_contact' => CustomerContact::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
