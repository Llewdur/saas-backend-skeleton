<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\UseCases;

use App\Models\User;
use App\Modules\Users\Application\DTOs\UpdateProfileInput;

final class UpdateProfile
{
    public function execute(User $user, UpdateProfileInput $input): User
    {
        $payload = array_filter(
            ['name' => $input->name, 'email' => $input->email],
            fn ($value): bool => $value !== null,
        );

        if ($payload === []) {
            return $user;
        }

        $user->fill($payload)->save();

        return $user->refresh();
    }
}
