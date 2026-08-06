<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    public function config(): JsonResponse
    {
        if (! app()->bound('current_tenant')) {
            return response()->json(null);
        }

        $tenant = app('current_tenant');

        return response()->json([
            'app_name' => $tenant->app_name,
            'logo_url' => $tenant->logo_url,
            'primary_color' => $tenant->primary_color,
            'secondary_color' => $tenant->secondary_color,
            'theme' => $tenant->theme,
            'subdomain' => $tenant->subdomain,
            'subscription_status' => $tenant->subscription_status,
        ]);
    }

    public function updateBranding(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'client', 403);

        $data = $request->validate([
            'app_name' => 'sometimes|nullable|string|max:100',
            'primary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme' => 'sometimes|nullable|in:minimal,modern,classic',
        ]);

        $data = array_filter($data, fn ($value) => $value !== null);

        $tenant = app('current_tenant');
        $tenant->update($data);

        return response()->json($tenant->only(['app_name', 'primary_color', 'secondary_color', 'theme', 'logo_url']));
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'client', 403);

        $request->validate(['logo' => 'required|image|max:2048']);

        $tenant = app('current_tenant');
        $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
        $tenant->update(['logo_url' => Storage::url($path)]);

        return response()->json(['logo_url' => $tenant->logo_url]);
    }
}
