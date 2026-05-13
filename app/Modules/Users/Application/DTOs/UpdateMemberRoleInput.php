<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\DTOs;

use App\Modules\Tenant\Domain\Enums\Role;

final readonly class UpdateMemberRoleInput
{
    public function __construct(
        public int $membershipId,
        public Role $role,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int $membershipId, array $data): self
    {
        return new self(
            membershipId: $membershipId,
            role: Role::from((string) ($data['role'] ?? '')),
        );
    }
}
