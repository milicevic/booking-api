<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleTenantCors
{
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

        // Lokalni razvoj: localhost i *.localhost
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        // Glavni FE domen (Vercel ili custom) — dozvoljen bez tenant provjere
        $allowedOrigins = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));
        foreach ($allowedOrigins as $allowed) {
            if (parse_url(trim($allowed), PHP_URL_HOST) === $host) {
                return true;
            }
        }

        // Platforma domen (npr. salon-a.slotres.click)
        $platformHost = env('PLATFORM_HOST', '');
        if ($platformHost && str_ends_with($host, '.'.$platformHost)) {
            $subdomain = substr($host, 0, -(strlen($platformHost) + 1));

            return Tenant::where('subdomain', $subdomain)->exists();
        }

        // Custom domena klijenta (npr. rezervacije.moj-salon.com)
        return Tenant::where('custom_domain', $host)->exists();
    }
}
