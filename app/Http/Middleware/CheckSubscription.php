<?php

namespace App\Http\Middleware;

use App\Services\Contracts\CurrentTenantInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function __construct(private readonly CurrentTenantInterface $currentTenant) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->currentTenant->get()?->subscription_status?->blocksAccess()) {
            return response()->json(['message' => __('messages.subscription_expired')], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
