<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Application\DTOs\UpdateMemberRoleInput;
use App\Modules\Users\Application\UseCases\UpdateMemberRole;
use App\Modules\Users\Domain\Exceptions\CannotDemoteLastOwner;
use App\Modules\Users\Domain\Exceptions\InsufficientRole;
use App\Modules\Users\Http\Requests\UpdateMemberRoleRequest;
use App\Modules\Users\Http\Resources\MemberResource;
use Illuminate\Http\JsonResponse;

final class UpdateMemberRoleController
{
    public function __invoke(
        UpdateMemberRoleRequest $request,
        int $membership,
        UpdateMemberRole $useCase,
    ): JsonResponse {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $updated = $useCase->execute(
                $user,
                UpdateMemberRoleInput::fromRequest($request, $membership),
            );
        } catch (InsufficientRole $e) {
            return new JsonResponse([
                'errors' => [['code' => 'insufficient_role', 'title' => 'Forbidden', 'detail' => $e->getMessage()]],
            ], 403);
        } catch (CannotDemoteLastOwner $e) {
            return new JsonResponse([
                'errors' => [['code' => 'cannot_demote_last_owner', 'title' => 'Conflict', 'detail' => $e->getMessage()]],
            ], 409);
        }

        return MemberResource::make($updated)->response();
    }
}
