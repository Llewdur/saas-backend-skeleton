<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Middleware;

use App\Modules\Tenant\Domain\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects the request with 400 if no tenant context resolved.
 * Apply to routes that require a tenant; pair with `auth:sanctum` and `tenant.resolve`.
 */
final class EnsureTenantContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->has()) {
            return new JsonResponse([
                'errors' => [[
                    'code' => 'tenant_context_required',
                    'title' => 'Tenant context required',
                    'detail' => 'Provide an X-Tenant header or authenticate as a user with at least one membership.',
                ]],
            ], 400);
        }

        return $next($request);
    }
}
