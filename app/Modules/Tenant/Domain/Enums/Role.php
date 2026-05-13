<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function canManageBilling(): bool
    {
        return match ($this) {
            self::Owner => true,
            self::Admin, self::Member, self::Viewer => false,
        };
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Member, self::Viewer => false,
        };
    }

    public function canWrite(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::Member => true,
            self::Viewer => false,
        };
    }
}
