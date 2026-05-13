<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\DTOs;

/**
 * Sparse update payload. Fields are only present if the caller included them.
 * Per coding standards §14: never null an unchanged field.
 */
final readonly class ProjectChanges
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public bool $nameChanged,
        public bool $descriptionChanged,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $nameChanged = array_key_exists('name', $data);
        $descriptionChanged = array_key_exists('description', $data);

        return new self(
            name: $nameChanged ? (string) $data['name'] : null,
            description: $descriptionChanged && $data['description'] !== null && $data['description'] !== ''
                ? (string) $data['description']
                : null,
            nameChanged: $nameChanged,
            descriptionChanged: $descriptionChanged,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toEloquentPayload(): array
    {
        $payload = [];
        if ($this->nameChanged && $this->name !== null) {
            $payload['name'] = $this->name;
        }
        if ($this->descriptionChanged) {
            $payload['description'] = $this->description;
        }

        return $payload;
    }
}
