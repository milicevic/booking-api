<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $tenant = Tenant::where('custom_domain', $host)->first();

        if (! $tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('subdomain', $subdomain)->first();
        }

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found.'], Response::HTTP_NOT_FOUND);
        }

        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
