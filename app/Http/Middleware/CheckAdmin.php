<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Propušta samo platformskog admina (uloga admin).
 */
class CheckAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== UserRole::Admin) {
            return response()->json(['message' => __('http.403')], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
