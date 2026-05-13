<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class TenantId implements Stringable
{
    public function __construct(public int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("TenantId must be positive, got {$value}");
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
