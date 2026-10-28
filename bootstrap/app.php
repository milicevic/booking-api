<?php

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckSuperadmin;
use App\Http\Middleware\HandleTenantCors;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetLocale;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleTenantCors::class);
        $middleware->append(SetLocale::class);

        $middleware->alias([
            'resolve.tenant' => ResolveTenant::class,
            'check.subscription' => CheckSubscription::class,
            'superadmin' => CheckSuperadmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Generičke poruke frameworka ("Unauthenticated.", "Not Found", prazan abort(403)...)
        // menjamo prevodom iz lang/{locale}/http.php; poruke koje šalje aplikacija ostaju iste.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            if (! $response instanceof JsonResponse || $response->getStatusCode() < 400) {
                return $response;
            }

            $status = $response->getStatusCode();
            $data = $response->getData(true);
            $message = $data['message'] ?? '';

            $isFrameworkMessage = $message === ''
                || $message === (Response::$statusTexts[$status] ?? null)
                || in_array($message, [
                    'Unauthenticated.',
                    'This action is unauthorized.',
                    'Forbidden.',
                    'Too Many Attempts.',
                    'Server Error',
                    'CSRF token mismatch.',
                    'Service Unavailable',
                ], true)
                || str_starts_with($message, 'No query results for model')
                || str_starts_with($message, 'The route ');

            if (! $isFrameworkMessage) {
                return $response;
            }

            // Za nepostojeći model konkretna poruka ("Rezervacija ne postoji…"), inače opšta po status kodu
            $notFound = $e instanceof ModelNotFoundException ? $e : $e->getPrevious();
            $modelKey = $notFound instanceof ModelNotFoundException
                ? 'messages.not_found.'.Str::snake(class_basename($notFound->getModel()))
                : null;

            if ($modelKey && Lang::has($modelKey)) {
                $data['message'] = __($modelKey);
            } elseif (Lang::has("http.{$status}")) {
                $data['message'] = __("http.{$status}");
            }

            $response->setData($data);

            return $response;
        });
    })->create();
