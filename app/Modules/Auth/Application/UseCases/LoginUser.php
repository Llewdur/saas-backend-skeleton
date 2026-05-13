<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\UseCases;

use App\Models\User;
use App\Modules\Auth\Application\DTOs\LoginInput;
use App\Modules\Auth\Application\DTOs\LoginResult;
use App\Modules\Auth\Domain\Exceptions\InvalidCredentials;
use Illuminate\Support\Facades\Hash;

final class LoginUser
{
    public function execute(LoginInput $input): LoginResult
    {
        $user = User::query()->where('email', $input->email)->first();

        if ($user === null || ! Hash::check($input->password, $user->password)) {
            throw InvalidCredentials::forEmail($input->email);
        }

        $token = $user->createToken($input->deviceName);

        return new LoginResult($user, $token->plainTextToken);
    }
}
