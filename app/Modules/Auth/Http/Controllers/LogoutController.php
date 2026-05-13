<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            // No bearer token present — session / TransientToken / nothing.
            // Don't silently 204; the caller asked for token revocation and
            // didn't get one. Be explicit so misconfigured clients fail loud.
            return new JsonResponse([
                'errors' => [[
                    'code' => 'no_bearer_token',
                    'title' => 'Bad Request',
                    'detail' => 'No bearer token to revoke. Authenticate with a Sanctum token before calling /auth/logout.',
                ]],
            ], 400);
        }

        $token->delete();

        return new JsonResponse(status: 204);
    }
}
