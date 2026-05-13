<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

final readonly class Slug implements Stringable
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid slug: {$value}");
        }
    }

    public static function fromName(string $name): self
    {
        return new self(Str::slug($name));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
