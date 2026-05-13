<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Tenant\Domain\Models\Membership;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lists memberships of the current tenant. Tenant scoping is delegated to
 * Membership's BelongsToTenant global scope — this use case doesn't need
 * to filter explicitly. The user model is eager-loaded so MemberResource
 * can serialise without N+1.
 */
final class ListMembers
{
    /**
     * @return Collection<int, Membership>
     */
    public function execute(): Collection
    {
        /** @var Collection<int, Membership> $memberships */
        $memberships = Membership::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        return $memberships;
    }
}
