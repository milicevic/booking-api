<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'custom_domain',
        'primary_color',
        'secondary_color',
        'logo_url',
        'app_name',
        'theme',
        'trial_ends_at',
        'subscription_status',
        'subscription_ends_at',
        'deploy_status',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function clientProfiles(): HasMany
    {
        return $this->hasMany(ClientProfile::class);
    }

    public function workerProfiles(): HasMany
    {
        return $this->hasMany(WorkerProfile::class);
    }
}
