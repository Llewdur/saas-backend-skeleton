<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Exceptions;

use App\Modules\Tenant\Domain\ValueObjects\TenantId;
use DomainException;

final class CrossTenantAccessAttempted extends DomainException
{
    public static function with(TenantId $current, TenantId $resource): self
    {
        return new self(
            "Tenant {$current} attempted to access resource owned by tenant {$resource}."
        );
    }
}
