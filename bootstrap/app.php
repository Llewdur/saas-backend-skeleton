<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Exceptions\InvalidCredentials;
use App\Modules\Tenant\Domain\Exceptions\CrossTenantAccessAttempted;
use App\Modules\Users\Domain\Exceptions\CannotModifySelf;
use App\Modules\Users\Domain\Exceptions\InsufficientRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

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
        // Map domain exceptions to HTTP responses here per CODING_STANDARDS.md §13.
        // Controllers stay free of try/catch for control flow — exceptions bubble.
        // Detail strings stay generic; the raw exception message is never echoed back.

        // These are user-driven control-flow exceptions, not errors. Without
        // dontReport, every failed login / forbidden role attempt / cross-
        // tenant probe gets forwarded to Sentry/Bugsnag/etc and pages oncall
        // on what is normal API traffic.
        $exceptions->dontReport([
            InvalidCredentials::class,
            InsufficientRole::class,
            CannotModifySelf::class,
            CrossTenantAccessAttempted::class,
        ]);

        $envelope = static fn (int $status, string $code, string $title, string $detail): JsonResponse => new JsonResponse(
            ['errors' => [['code' => $code, 'title' => $title, 'detail' => $detail]]],
            $status,
        );

        $exceptions->render(fn (InvalidCredentials $_e, $_request): JsonResponse => $envelope(
            401, 'invalid_credentials', 'Invalid credentials', 'The email or password is incorrect.',
        ));

        $exceptions->render(fn (InsufficientRole $_e, $_request): JsonResponse => $envelope(
            403, 'insufficient_role', 'Forbidden', 'You do not have permission to perform this action.',
        ));

        $exceptions->render(fn (CannotModifySelf $_e, $_request): JsonResponse => $envelope(
            403, 'cannot_modify_self', 'Forbidden', 'You cannot modify your own role.',
        ));

        $exceptions->render(fn (CrossTenantAccessAttempted $_e, $_request): JsonResponse => $envelope(
            403, 'cross_tenant_access', 'Forbidden', 'Cross-tenant access is not permitted.',
        ));
    })->create();
