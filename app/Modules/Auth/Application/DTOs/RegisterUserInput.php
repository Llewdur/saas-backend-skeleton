<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

use App\Modules\Auth\Http\Requests\RegisterRequest;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $tenantName,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            tenantName: $request->string('tenant_name')->toString(),
        );
    }
}
