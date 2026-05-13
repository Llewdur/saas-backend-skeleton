<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\DTOs;

use App\Modules\Core\Http\Requests\UpdateProjectRequest;

/**
 * Sparse update payload. Fields are only present if the request included them.
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

    public static function fromRequest(UpdateProjectRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name')->toString() : null,
            description: $request->has('description') && $request->filled('description')
                ? $request->string('description')->toString()
                : null,
            nameChanged: $request->has('name'),
            descriptionChanged: $request->has('description'),
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
