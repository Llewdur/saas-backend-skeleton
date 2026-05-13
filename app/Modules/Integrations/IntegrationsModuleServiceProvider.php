<?php

declare(strict_types=1);

namespace App\Modules\Integrations;

use App\Modules\Core\Domain\Events\ProjectCreated;
use App\Modules\Integrations\Infrastructure\Listeners\FanOutProjectCreated;
use App\Support\ModuleServiceProvider;

final class IntegrationsModuleServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, array<int, class-string>> */
    public array $listen = [
        ProjectCreated::class => [
            FanOutProjectCreated::class,
        ],
    ];
}
