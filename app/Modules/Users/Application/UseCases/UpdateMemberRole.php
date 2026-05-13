<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\UseCases;

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\TenantContext;
use App\Modules\Users\Application\DTOs\UpdateMemberRoleInput;
use App\Modules\Users\Domain\Exceptions\CannotDemoteLastOwner;
use App\Modules\Users\Domain\Exceptions\InsufficientRole;

final class UpdateMemberRole
{
    public function __construct(private readonly TenantContext $context) {}

    public function execute(User $actor, UpdateMemberRoleInput $input): Membership
    {
        $actorMembership = Membership::query()
            ->where('user_id', $actor->id)
            ->firstOrFail();

        if (! $actorMembership->role->canManageMembers()) {
            throw InsufficientRole::forAction('manage members');
        }

        $target = Membership::query()->findOrFail($input->membershipId);

        if ($input->role !== Role::Owner && $target->role === Role::Owner) {
            $remainingOwners = Membership::query()
                ->where('role', Role::Owner->value)
                ->where('id', '!=', $target->id)
                ->count();

            if ($remainingOwners === 0) {
                throw CannotDemoteLastOwner::forTenant($this->context->id());
            }
        }

        $target->role = $input->role;
        $target->save();

        return $target->refresh();
    }
}
