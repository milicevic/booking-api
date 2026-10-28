<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class TenantController extends Controller
{
    /**
     * Najveća širina/visina sačuvanog loga u pikselima. Logo se prikazuje na 24–32px,
     * pa 512px ostavlja dovoljno prostora za retina ekrane i buduću upotrebu (favicon, mail).
     */
    private const LOGO_MAX_SIZE = 512;

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
            'currency' => $tenant->currency,
            'locale' => $tenant->locale,
            'subscription_status' => $tenant->subscription_status,
            'trial_ends_at' => $tenant->trial_ends_at,
            'subscription_ends_at' => $tenant->subscription_ends_at,
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
            'currency' => ['sometimes', Rule::in(Tenant::CURRENCIES)],
            'locale' => ['sometimes', Rule::in(Tenant::LOCALES)],
        ]);

        $data = array_filter($data, fn ($value) => $value !== null);

        $tenant = app('current_tenant');
        $tenant->update($data);

        return response()->json($tenant->only(['app_name', 'primary_color', 'secondary_color', 'theme', 'logo_url', 'currency', 'locale']));
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'client', 403);

        $request->validate([
            'logo' => 'required|mimes:png,jpg,jpeg,webp|max:2048|dimensions:min_width=128,min_height=128',
        ]);

        $logo = ImageManager::usingDriver(Driver::class)
            ->decodePath($request->file('logo')->getRealPath())
            ->scaleDown(self::LOGO_MAX_SIZE, self::LOGO_MAX_SIZE)
            ->encode(new WebpEncoder(quality: 85, strip: true));

        $tenant = app('current_tenant');
        $path = "logos/{$tenant->id}/".Str::random(40).'.webp';
        Storage::disk('public')->put($path, $logo->toString());
        $tenant->update(['logo_url' => Storage::url($path)]);

        return response()->json(['logo_url' => $tenant->logo_url]);
    }
}
