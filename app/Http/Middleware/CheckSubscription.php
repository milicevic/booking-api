<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('current_tenant')) {
            return $next($request);
        }

        $tenant = app('current_tenant');

        if (in_array($tenant->subscription_status, ['expired', 'canceled'])) {
            return response()->json(['message' => 'Subscription is inactive.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
