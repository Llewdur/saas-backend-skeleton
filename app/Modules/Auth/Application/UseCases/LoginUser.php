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
    /**
     * Used to give every login-miss the same hashing cost as a hit. Without
     * this, an attacker can time the response and learn which emails exist.
     *
     * Must be a valid bcrypt hash (60 characters: $2y$ + cost + $ + 53-char
     * salt/hash). Hash::check() rejects malformed hashes cheaply, which would
     * itself become a timing oracle.
     */
    private const DUMMY_HASH = '$2y$12$abcdefghijklmnopqrstuvabcdefghijklmnopqrstuvabcdefghij';

    public function execute(LoginInput $input): LoginResult
    {
        $user = User::query()->where('email', $input->email)->first();

        // Always run a hash check so the response time doesn't leak existence.
        $hash = $user !== null ? (string) $user->password : self::DUMMY_HASH;
        $passwordOk = Hash::check($input->password, $hash);

        if ($user === null || ! $passwordOk) {
            throw InvalidCredentials::forEmail($input->email);
        }

        // Token abilities: one entry per tenant the user is a member of at
        // login time. ResolveTenant checks the ability before binding context,
        // so a stolen token's blast radius is the tenants present at login.
        //
        // If the user has zero memberships, abilities are empty. The token
        // still authenticates (so /auth/me works) but won't grant any
        // tenant-scoped route. Crucially we do NOT fall back to '*' — that
        // would give a brand-new account access to every tenant they later
        // join, defeating the binding.
        $abilities = $user->memberships()
            ->pluck('tenant_id')
            ->map(fn (int $tenantId): string => "tenant:{$tenantId}")
            ->all();

        $token = $user->createToken($input->deviceName, $abilities);

        return new LoginResult($user, $token->plainTextToken);
    }
}
