<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\DTOs;

final readonly class ProjectInput
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $description = array_key_exists('description', $data) ? $data['description'] : null;

        return new self(
            name: (string) ($data['name'] ?? ''),
            description: $description === null || $description === '' ? null : (string) $description,
        );
    }
}
