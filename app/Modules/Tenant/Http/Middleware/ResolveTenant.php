<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Middleware;

use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Domain\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the current request and binds it into TenantContext.
 *
 * Resolution order:
 *   1. `X-Tenant` header (slug, then numeric ID fallback)
 *   2. Authenticated user's first membership
 *
 * If nothing resolves, the context is left empty; downstream middleware
 * (EnsureTenantContext) decides whether that's an error.
 */
final class ResolveTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveFromHeader($request)
            ?? $this->resolveFromUser($request);

        if ($tenant !== null) {
            $this->context->set($tenant);
        }

        return $next($request);
    }

    private function resolveFromHeader(Request $request): ?Tenant
    {
        $hint = $request->header('X-Tenant');
        if (! is_string($hint) || $hint === '') {
            return null;
        }

        $byNumeric = ctype_digit($hint)
            ? Tenant::query()->find((int) $hint)
            : null;

        return $byNumeric ?? Tenant::query()->where('slug', $hint)->first();
    }

    private function resolveFromUser(Request $request): ?Tenant
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        $membership = $user->memberships()->orderBy('id')->first();

        return $membership?->tenant;
    }
}
