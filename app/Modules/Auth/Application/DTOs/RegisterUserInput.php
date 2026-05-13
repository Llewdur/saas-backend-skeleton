<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $tenantName,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            tenantName: (string) ($data['tenant_name'] ?? ''),
        );
    }
}
