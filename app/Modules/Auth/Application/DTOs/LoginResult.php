<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

use App\Models\User;

final readonly class LoginResult
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
    ) {}
}
