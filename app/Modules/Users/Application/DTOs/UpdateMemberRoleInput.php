<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\DTOs;

use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Users\Http\Requests\UpdateMemberRoleRequest;

final readonly class UpdateMemberRoleInput
{
    public function __construct(
        public int $membershipId,
        public Role $role,
    ) {}

    public static function fromRequest(UpdateMemberRoleRequest $request, int $membershipId): self
    {
        return new self(
            membershipId: $membershipId,
            role: Role::from($request->string('role')->toString()),
        );
    }
}
