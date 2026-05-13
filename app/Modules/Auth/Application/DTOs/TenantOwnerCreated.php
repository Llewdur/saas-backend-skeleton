<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

use App\Models\User;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;

/**
 * Result of TenantOwnerFactory::create — the three rows that materialise
 * a new tenant with its founding owner.
 */
final readonly class TenantOwnerCreated
{
    public function __construct(
        public User $user,
        public Tenant $tenant,
        public Membership $membership,
    ) {}
}
