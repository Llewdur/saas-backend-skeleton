<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

use App\Modules\Auth\Http\Requests\LoginRequest;

final readonly class LoginInput
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name', 'api')->toString(),
        );
    }
}
