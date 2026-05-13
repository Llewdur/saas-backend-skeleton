<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Modules\Auth\Domain\Factories\TenantOwnerFactory;
use App\Modules\Auth\Infrastructure\Factories\EloquentTenantOwnerFactory;
use App\Support\ModuleServiceProvider;

final class AuthModuleServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        TenantOwnerFactory::class => EloquentTenantOwnerFactory::class,
    ];
}
