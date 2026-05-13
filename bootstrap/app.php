<?php

declare(strict_types=1);

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
        // tenant.resolve must run BEFORE SubstituteBindings so route-model
        // binding sees the tenant context (otherwise the global scope can't filter).
        $middleware->api(prepend: [
            'tenant.resolve',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
