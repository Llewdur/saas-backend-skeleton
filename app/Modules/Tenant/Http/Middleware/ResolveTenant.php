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
 * Resolution rules:
 *   1. If `X-Tenant` header is present, look up the tenant by slug (or numeric
 *      id fallback). The authenticated user MUST have a membership in that
 *      tenant — otherwise the header is silently ignored and we fall through.
 *      This prevents authenticated users from impersonating tenants they
 *      don't belong to.
 *   2. Otherwise, use the authenticated user's first membership (deterministic
 *      ordering by id).
 *   3. If nothing resolves, the context is left empty; downstream middleware
 *      (`tenant.ensure`) decides whether that is an error for the route.
 *
 * The context is always cleared at the start of every request — under Octane
 * / Swoole / RoadRunner the singleton survives across requests if we don't.
 */
final class ResolveTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

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

        $tenant = ctype_digit($hint)
            ? Tenant::query()->find((int) $hint)
            : Tenant::query()->where('slug', $hint)->first();

        if ($tenant === null) {
            return null;
        }

        $user = $request->user();
        if ($user === null) {
            return null;
        }

        // Critical: the user must actually be a member of the requested tenant.
        // Without this check, any authenticated user can read/write any tenant.
        $isMember = $user->memberships()->where('tenant_id', $tenant->id)->exists();
        if (! $isMember) {
            return null;
        }

        // If the request is bearer-token-authenticated, the token must hold the
        // tenant:{id} ability granted at login. This contains a stolen token's
        // blast radius to the tenants present when the token was issued.
        if (method_exists($user, 'currentAccessToken')
            && $user->currentAccessToken() !== null
            && ! $user->tokenCan("tenant:{$tenant->id}")
        ) {
            return null;
        }

        return $tenant;
    }

    private function resolveFromUser(Request $request): ?Tenant
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        $hasToken = method_exists($user, 'currentAccessToken') && $user->currentAccessToken() !== null;

        foreach ($user->memberships()->orderBy('id')->get() as $membership) {
            // Without a bearer token (session / web / actingAs), the ability
            // check doesn't apply — return the first membership. With a token,
            // pick the first tenant whose ability is on the token (so a
            // stolen token can't fall through to a tenant the user later
            // joined after token issue).
            if (! $hasToken || $user->tokenCan("tenant:{$membership->tenant_id}")) {
                return $membership->tenant;
            }
        }

        return null;
    }
}
