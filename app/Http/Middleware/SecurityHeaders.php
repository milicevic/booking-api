<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bezbednosna zaglavlja za sve odgovore API-ja.
 *
 * JSON odgovori dobijaju CSP bez ikakvog sadržaja; HTML stranice (upravljanje rezervacijom, pregled
 * rezervacije iz mejla) koriste samo svoje ugrađene stilove i forme. HSTS se šalje samo preko HTTPS-a.
 */
class SecurityHeaders
{
    private const HTML_CSP = "default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'; base-uri 'none'; frame-ancestors 'none'";

    private const API_CSP = "default-src 'none'; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $isHtml = str_contains((string) $response->headers->get('Content-Type'), 'text/html');

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Content-Security-Policy' => $isHtml ? self::HTML_CSP : self::API_CSP,
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
