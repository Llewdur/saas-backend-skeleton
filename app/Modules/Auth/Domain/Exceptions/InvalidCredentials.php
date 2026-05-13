<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Exceptions;

use DomainException;

final class InvalidCredentials extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self("Invalid credentials for {$email}.");
    }
}
