<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use DomainException;

final class InsufficientRole extends DomainException
{
    public static function forAction(string $action): self
    {
        return new self("Current role is not permitted to {$action}.");
    }
}
