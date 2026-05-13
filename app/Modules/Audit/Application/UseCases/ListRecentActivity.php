<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\UseCases;

use App\Modules\Audit\Domain\Repositories\ActivityRepository;
use App\Modules\Tenant\Domain\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

final class ListRecentActivity
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly ActivityRepository $repository,
    ) {}

    /**
     * @return Collection<int, Activity>
     */
    public function execute(int $limit = 100): Collection
    {
        return $this->repository->recentForTenant($this->context->tenantId(), $limit);
    }
}
