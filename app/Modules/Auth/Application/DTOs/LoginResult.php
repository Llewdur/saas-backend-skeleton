<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

use App\Models\User;
use SensitiveParameter;

final readonly class LoginResult
{
    public function __construct(
        public User $user,
        #[SensitiveParameter]
        public string $plainTextToken,
    ) {}
}
