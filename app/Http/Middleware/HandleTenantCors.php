<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\TenantRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleTenantCors
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        if ($origin && $this->isAllowedOrigin($origin)) {
            if ($request->getMethod() === 'OPTIONS') {
                return response('', 204)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Tenant-Subdomain, X-Tenant-Domain')
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header('Access-Control-Max-Age', '86400');
            }

            $response = $next($request);

            return $response
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Tenant-Subdomain, X-Tenant-Domain')
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        return $next($request);
    }

    private function isAllowedOrigin(?string $origin): bool
    {
        if (! $origin) {
            return false;
        }

        $host = parse_url($origin, PHP_URL_HOST);
        if (! $host) {
            return false;
        }

        // Lokalni razvoj: localhost i *.localhost (samo lokalno i u testovima, nikad na produkciji)
        if (app()->environment(['local', 'testing']) && ($host === 'localhost' || str_ends_with($host, '.localhost'))) {
            return true;
        }

        // Tačno navedeni frontend domeni (CORS_ALLOWED_ORIGINS), dozvoljeni bez tenant provere
        $allowedOrigins = array_filter(explode(',', (string) config('app.cors_allowed_origins')));
        foreach ($allowedOrigins as $allowed) {
            if (parse_url(trim($allowed), PHP_URL_HOST) === $host) {
                return true;
            }
        }

        // Sajt firme: poddomen platforme (salon-a.slotres.click) ili custom domen (rezervacije.moj-salon.com)
        return $this->tenants->findByHost($host) !== null;
    }
}
