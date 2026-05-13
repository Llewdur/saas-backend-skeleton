<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Application\DTOs\LoginInput;
use App\Modules\Auth\Application\UseCases\LoginUser;
use App\Modules\Auth\Domain\Exceptions\InvalidCredentials;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __invoke(LoginRequest $request, LoginUser $useCase): JsonResponse
    {
        try {
            $result = $useCase->execute(LoginInput::fromArray($request->validated()));
        } catch (InvalidCredentials) {
            return new JsonResponse([
                'errors' => [[
                    'code' => 'invalid_credentials',
                    'title' => 'Invalid credentials',
                    'detail' => 'The email or password is incorrect.',
                ]],
            ], 401);
        }

        return new JsonResponse([
            'data' => [
                'token' => $result->plainTextToken,
                'user' => UserResource::make($result->user)->resolve(),
            ],
        ], 200);
    }
}
