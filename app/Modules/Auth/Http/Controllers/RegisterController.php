<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Application\DTOs\RegisterUserInput;
use App\Modules\Auth\Application\UseCases\RegisterUser;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class RegisterController
{
    public function __invoke(RegisterRequest $request, RegisterUser $useCase): JsonResponse
    {
        $user = $useCase->execute(RegisterUserInput::fromArray($request->validated()));

        return UserResource::make($user)->response()->setStatusCode(201);
    }
}
