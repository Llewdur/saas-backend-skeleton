<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence;

use App\Modules\Audit\Domain\Repositories\ActivityRepository;
use App\Modules\Tenant\Domain\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

final class EloquentActivityRepository implements ActivityRepository
{
    /**
     * @return Collection<int, Activity>
     */
    public function recentForTenant(TenantId $tenantId, int $limit): Collection
    {
        /** @var Collection<int, Activity> $activities */
        $activities = Activity::query()
            ->where('tenant_id', $tenantId->value)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $activities;
    }
}
