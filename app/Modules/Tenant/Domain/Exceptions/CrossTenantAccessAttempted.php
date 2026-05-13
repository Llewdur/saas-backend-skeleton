<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Exceptions;

use DomainException;

final class CrossTenantAccessAttempted extends DomainException
{
    public static function with(int $currentTenantId, int $resourceTenantId): self
    {
        return new self(
            "Tenant {$currentTenantId} attempted to access resource owned by tenant {$resourceTenantId}."
        );
    }
}
