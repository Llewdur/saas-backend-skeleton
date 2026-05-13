<?php

declare(strict_types=1);

use App\Modules\Audit\AuditModuleServiceProvider;
use App\Modules\Auth\AuthModuleServiceProvider;
use App\Modules\Billing\BillingModuleServiceProvider;
use App\Modules\Core\CoreModuleServiceProvider;
use App\Modules\Integrations\IntegrationsModuleServiceProvider;
use App\Modules\Tenant\TenantModuleServiceProvider;
use App\Modules\Users\UsersModuleServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,

    TenantModuleServiceProvider::class,
    AuthModuleServiceProvider::class,
    UsersModuleServiceProvider::class,
    CoreModuleServiceProvider::class,
    BillingModuleServiceProvider::class,
    AuditModuleServiceProvider::class,
    IntegrationsModuleServiceProvider::class,
];
