<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

use SensitiveParameter;

final readonly class LoginInput
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $password,
        public string $deviceName,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            deviceName: (string) ($data['device_name'] ?? 'api'),
        );
    }
}
