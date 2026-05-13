<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Models\User;
use App\Modules\Users\Application\DTOs\UpdateMemberRoleInput;
use App\Modules\Users\Application\UseCases\UpdateMemberRole;
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
        /** @var User $user */
        $user = $request->user();

        $updated = $useCase->execute(
            $user,
            UpdateMemberRoleInput::fromArray($membership, $request->validated()),
        );

        return MemberResource::make($updated)->response();
    }
}
