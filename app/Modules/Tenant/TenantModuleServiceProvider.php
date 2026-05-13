<?php

declare(strict_types=1);

namespace App\Modules\Tenant;

use App\Modules\Tenant\Domain\TenantContext;
use App\Modules\Tenant\Http\Middleware\EnsureTenantContext;
use App\Modules\Tenant\Http\Middleware\ResolveTenant;
use App\Support\ModuleServiceProvider;
use Illuminate\Routing\Router;

final class TenantModuleServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        // `scoped` (not `singleton`) — under Octane / Swoole / RoadRunner the
        // app instance survives across requests, so a singleton would leak
        // tenant context. `scoped` is per-request and Laravel resets it
        // automatically on Octane request boundaries.
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext);
    }

    public function boot(): void
    {
        parent::boot();

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('tenant.resolve', ResolveTenant::class);
        $router->aliasMiddleware('tenant.ensure', EnsureTenantContext::class);
    }
}
