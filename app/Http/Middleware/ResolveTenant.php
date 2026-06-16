<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $host = $request->getHost();

        $tenant = Tenant::where('custom_domain', $host)->first();

        if (! $tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('subdomain', $subdomain)->first();
        }

        if (! $tenant && $request->hasHeader('X-Tenant-Subdomain')) {
            $tenant = Tenant::where('subdomain', $request->header('X-Tenant-Subdomain'))->first();
        }

        if (! $tenant && $mode === 'required') {
            return response()->json(['message' => __('messages.tenant_not_found')], Response::HTTP_NOT_FOUND);
        }

        if ($tenant) {
            app()->instance('current_tenant', $tenant);
        }

        return $next($request);
    }
}
