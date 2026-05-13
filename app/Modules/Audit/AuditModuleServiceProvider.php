<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use App\Modules\Audit\Domain\Repositories\ActivityRepository;
use App\Modules\Audit\Infrastructure\Listeners\StampActivityWithTenant;
use App\Modules\Audit\Infrastructure\Persistence\EloquentActivityRepository;
use App\Support\ModuleServiceProvider;
use Spatie\Activitylog\Models\Activity;

final class AuditModuleServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ActivityRepository::class => EloquentActivityRepository::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Every activity log row is stamped with the current tenant_id so
        // the /api/v1/audit endpoint can filter directly rather than joining
        // through subject types.
        Activity::creating(function (Activity $activity): void {
            app(StampActivityWithTenant::class)($activity);
        });
    }
}
