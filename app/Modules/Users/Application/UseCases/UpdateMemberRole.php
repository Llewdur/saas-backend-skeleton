<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\UseCases;

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\TenantContext;
use App\Modules\Users\Application\DTOs\UpdateMemberRoleInput;
use App\Modules\Users\Domain\Exceptions\CannotModifySelf;
use App\Modules\Users\Domain\Exceptions\InsufficientRole;

final class UpdateMemberRole
{
    public function __construct(private readonly TenantContext $context) {}

    public function execute(User $actor, UpdateMemberRoleInput $input): Membership
    {
        $actorMembership = Membership::query()
            ->where('user_id', $actor->id)
            ->where('tenant_id', $this->context->id())
            ->first();

        if ($actorMembership === null || ! $actorMembership->role->canManageMembers()) {
            throw InsufficientRole::forAction('manage members');
        }

        $target = Membership::query()->findOrFail($input->membershipId);

        if ($target->user_id === $actor->id) {
            throw CannotModifySelf::forRoleChange();
        }

        // Only Owners can grant Owner or modify an Owner. Anything else from an
        // Admin actor is allowed. Note: the structural consequence of this rule
        // is that the "last Owner" cannot be demoted via this use case — the
        // only actor permitted to demote an Owner is themselves an Owner,
        // which means after demotion there's still at least one Owner left.
        $isPromotingToOwner = $input->role === Role::Owner;
        $isModifyingOwner = $target->role === Role::Owner;

        if (($isPromotingToOwner || $isModifyingOwner) && $actorMembership->role !== Role::Owner) {
            throw InsufficientRole::forAction('grant or modify the Owner role');
        }

        $target->role = $input->role;
        $target->save();

        return $target->refresh();
    }
}
