<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Domain\Models\Project;
use Illuminate\Pagination\CursorPaginator;

/**
 * Lists projects in the current tenant. Tenant scoping is delegated to
 * Project's BelongsToTenant global scope. Cursor pagination avoids the
 * COUNT(*) round-trip that offset paginate() incurs on every request.
 */
final class ListProjects
{
    /**
     * @return CursorPaginator<int, Project>
     */
    public function execute(int $perPage = 25): CursorPaginator
    {
        return Project::query()
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
