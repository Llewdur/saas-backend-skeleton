<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use DomainException;

final class CannotDemoteLastOwner extends DomainException
{
    public static function forTenant(int $tenantId): self
    {
        return new self("Cannot demote the last Owner of tenant {$tenantId}.");
    }
}
