<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Users\Application\DTOs\UpdateProfileInput;
use App\Modules\Users\Application\UseCases\UpdateProfile;
use App\Modules\Users\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

final class UpdateProfileController
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $useCase): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $updated = $useCase->execute($user, UpdateProfileInput::fromRequest($request));

        return UserResource::make($updated)->response();
    }
}
