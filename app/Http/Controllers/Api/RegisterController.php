<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Notifications\WelcomeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'app_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['nullable', 'string', 'alpha_dash', 'max:63', 'unique:tenants,subdomain'],
        ]);

        [$tenant, $user] = Tenant::createWithClient($data, $data);
        $token = $user->createToken('api-token')->plainTextToken;

        $user->notify(new WelcomeClient($tenant));

        return response()->json([
            'token' => $token,
            'tenant' => $tenant,
            'user' => $user,
        ], 201);
    }

    /**
     * Prikazuje adresu (poddomen) koju će firma dobiti, dok korisnik kuca naziv na registraciji.
     */
    public function subdomainPreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'subdomain' => Tenant::uniqueSubdomainFor($data['app_name']),
        ]);
    }
}
