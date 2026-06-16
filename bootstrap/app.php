<?php

use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CheckSuperadmin;
use App\Http\Middleware\HandleTenantCors;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        //
    })->create();
