<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Listeners;

use App\Modules\Tenant\Domain\TenantContext;
use Spatie\Activitylog\Models\Activity;

/**
 * Sets tenant_id on every activity log entry from the current tenant context.
 *
 * Wired via Activity::creating in AuditModuleServiceProvider. If there is no
 * tenant in context (system-level event, CLI seeder, queue worker without
 * tenant binding), tenant_id stays null and the entry is invisible to any
 * tenant's audit feed — by design.
 */
final class StampActivityWithTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function __invoke(Activity $activity): void
    {
        if (! $this->context->has()) {
            return;
        }

        $activity->setAttribute('tenant_id', $this->context->id());
    }
}
