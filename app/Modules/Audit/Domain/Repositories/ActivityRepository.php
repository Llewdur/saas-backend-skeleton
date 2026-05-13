<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Repositories;

use App\Modules\Tenant\Domain\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

interface ActivityRepository
{
    /**
     * @return Collection<int, Activity>
     */
    public function recentForTenant(TenantId $tenantId, int $limit): Collection;
}
