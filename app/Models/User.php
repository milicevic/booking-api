<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'role', 'client_id', 'can_edit_slots', 'is_suspended', 'tenant_id', 'invite_token'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'can_edit_slots' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    /** Workers belonging to this client user. */
    public function workers()
    {
        return $this->hasMany(User::class, 'client_id')->where('role', 'worker');
    }

    /** The client user this worker belongs to. */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function clientProfile()
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'worker_services', 'worker_id', 'service_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
