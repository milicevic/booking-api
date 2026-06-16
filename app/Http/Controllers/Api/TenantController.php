<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

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
}
