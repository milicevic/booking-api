<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WelcomeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', Rule::in(['minimal', 'modern', 'classic'])],
        ]);

        $subdomain = $data['subdomain'] ?? $this->generateUniqueSubdomain($data['app_name']);

        [$tenant, $user, $token] = DB::transaction(function () use ($data, $subdomain): array {
            $tenant = Tenant::create([
                'name' => $data['app_name'],
                'subdomain' => $subdomain,
                'app_name' => $data['app_name'],
                'primary_color' => $data['primary_color'] ?? null,
                'secondary_color' => $data['secondary_color'] ?? null,
                'theme' => $data['theme'] ?? 'minimal',
                'subscription_status' => 'trialing',
                'trial_ends_at' => now()->addDays(7),
            ]);

            /** @var User $user */
            $user = User::withoutGlobalScopes()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'client',
                'tenant_id' => $tenant->id,
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            return [$tenant, $user, $token];
        });

        $user->notify(new WelcomeClient($tenant));

        return response()->json([
            'token' => $token,
            'tenant' => $tenant,
            'user' => $user,
        ], 201);
    }

    private function generateUniqueSubdomain(string $appName): string
    {
        $base = Str::slug($appName);
        $subdomain = $base;
        $counter = 1;

        while (Tenant::where('subdomain', $subdomain)->exists()) {
            $subdomain = "{$base}-{$counter}";
            $counter++;
        }

        return $subdomain;
    }
}
