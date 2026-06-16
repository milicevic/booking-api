<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'sr'];

    /**
     * Set application locale from Accept-Language header or ?locale= query param.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('locale')
            ?? $this->parseAcceptLanguage($request->header('Accept-Language', ''));

        if (in_array($locale, self::SUPPORTED_LOCALES)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function parseAcceptLanguage(string $header): ?string
    {
        // Accept-Language: sr-RS,sr;q=0.9,en;q=0.8 → extract first tag
        preg_match('/^([a-zA-Z]{2})/', $header, $matches);

        return $matches[1] ?? null;
    }
}
