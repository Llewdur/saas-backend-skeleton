<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\DTOs;

use App\Modules\Core\Http\Requests\StoreProjectRequest;

final readonly class ProjectInput
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreProjectRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );
    }
}
