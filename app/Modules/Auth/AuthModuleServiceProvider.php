<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Modules\Auth\Domain\Factories\TenantOwnerFactory;
use App\Modules\Auth\Infrastructure\Factories\EloquentTenantOwnerFactory;
use App\Support\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AuthModuleServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        TenantOwnerFactory::class => EloquentTenantOwnerFactory::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Aggressive throttle on the unauthenticated auth surface. 5 attempts
        // per minute per IP is enough for honest users and prohibitive for
        // enumeration / brute-force.
        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(5)->by(
            (string) ($request->ip() ?? 'unknown'),
        ));
    }
}
