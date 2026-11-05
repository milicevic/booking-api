<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\ClientServiceInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function __construct(private readonly ClientServiceInterface $clients) {}

    public function clients(): AnonymousResourceCollection
    {
        Gate::authorize('viewAnyClients', User::class);

        return UserResource::collection($this->clients->allClients());
    }

    public function suspendClient(User $client): UserResource
    {
        Gate::authorize('suspend', $client);

        return UserResource::make($this->clients->toggleSuspension($client));
    }
}
