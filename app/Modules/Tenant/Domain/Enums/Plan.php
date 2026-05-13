<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Enums;

enum Plan: string
{
    case Free = 'free';
    case Starter = 'starter';
    case Pro = 'pro';

    public function isPaid(): bool
    {
        return match ($this) {
            self::Free => false,
            self::Starter, self::Pro => true,
        };
    }
}
