<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuperadminController extends Controller
{
    public function tenants(): JsonResponse
    {
        $tenants = Tenant::withCount('users')->orderBy('created_at', 'desc')->get();

        return response()->json($tenants);
    }

    public function showTenant(Tenant $tenant): JsonResponse
    {
        return response()->json($tenant->load('users'));
    }

    public function updateSubscription(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'subscription_status' => ['required', Rule::in(['trialing', 'active', 'expired', 'canceled'])],
            'subscription_ends_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($validated);

        return response()->json($tenant->fresh());
    }

    public function updateDeployStatus(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'deploy_status' => ['required', Rule::in(['pending', 'pending_deploy', 'deployed'])],
        ]);

        $tenant->update($validated);

        return response()->json($tenant->fresh());
    }

    public function updateTheme(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', Rule::in(['minimal', 'modern', 'classic'])],
        ]);

        $tenant->update($validated);

        return response()->json($tenant->fresh());
    }
}
