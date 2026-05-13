<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Exceptions;

use DomainException;

final class TenantContextMissing extends DomainException
{
    public static function forOperation(string $operation): self
    {
        return new self("No tenant context bound; cannot {$operation}.");
    }
}
