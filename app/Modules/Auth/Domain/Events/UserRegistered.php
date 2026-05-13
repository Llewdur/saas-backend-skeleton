<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Events;

use App\Models\User;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class UserRegistered
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Tenant $tenant,
    ) {}
}
