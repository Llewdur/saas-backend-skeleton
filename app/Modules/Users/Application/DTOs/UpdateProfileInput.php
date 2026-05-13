<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\DTOs;

use App\Modules\Users\Http\Requests\UpdateProfileRequest;

final readonly class UpdateProfileInput
{
    public function __construct(
        public ?string $name,
        public ?string $email,
    ) {}

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name')->toString() : null,
            email: $request->has('email') ? $request->string('email')->toString() : null,
        );
    }
}
