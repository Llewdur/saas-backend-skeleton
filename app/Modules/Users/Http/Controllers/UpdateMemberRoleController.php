<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Models\User;
use App\Modules\Users\Application\DTOs\UpdateMemberRoleInput;
use App\Modules\Users\Application\UseCases\UpdateMemberRole;
use App\Modules\Users\Domain\Exceptions\CannotModifySelf;
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
        /** @var User $user */
        $user = $request->user();

        try {
            $updated = $useCase->execute(
                $user,
                UpdateMemberRoleInput::fromArray($membership, $request->validated()),
            );
        } catch (InsufficientRole) {
            return $this->error(403, 'insufficient_role', 'Forbidden', 'You do not have permission to perform this action.');
        } catch (CannotModifySelf) {
            return $this->error(403, 'cannot_modify_self', 'Forbidden', 'You cannot modify your own role.');
        }

        return MemberResource::make($updated)->response();
    }

    private function error(int $status, string $code, string $title, string $detail): JsonResponse
    {
        return new JsonResponse([
            'errors' => [['code' => $code, 'title' => $title, 'detail' => $detail]],
        ], $status);
    }
}
