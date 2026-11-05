<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // API vraća resurse bez "data" omotača, kao i pre uvođenja API Resource klasa
        JsonResource::withoutWrapping();
    }
}
