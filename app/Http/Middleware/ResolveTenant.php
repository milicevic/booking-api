<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\Contracts\CurrentTenantInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly CurrentTenantInterface $currentTenant,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $subdomainHeader = $request->header('X-Tenant-Subdomain');
        $tenant = $this->tenants->findForRequest($request->getHost(), is_string($subdomainHeader) ? $subdomainHeader : null);

        if (! $tenant && $mode === 'required') {
            return response()->json(['message' => __('messages.tenant_not_found')], Response::HTTP_NOT_FOUND);
        }

        if ($tenant) {
            $this->currentTenant->set($tenant);
        }

        return $next($request);
    }
}
