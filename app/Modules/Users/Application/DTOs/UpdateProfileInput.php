<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\DTOs;

final readonly class UpdateProfileInput
{
    public function __construct(
        public ?string $name,
        public ?string $email,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: array_key_exists('name', $data) ? (string) $data['name'] : null,
            email: array_key_exists('email', $data) ? (string) $data['email'] : null,
        );
    }
}
