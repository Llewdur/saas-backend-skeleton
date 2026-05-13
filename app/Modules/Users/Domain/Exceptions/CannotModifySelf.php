<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use DomainException;

final class CannotModifySelf extends DomainException
{
    public static function forRoleChange(): self
    {
        return new self('Members cannot change their own role; ask another Owner.');
    }
}
