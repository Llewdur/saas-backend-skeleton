<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Resources;

use App\Modules\Tenant\Domain\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Membership
 */
final class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        assert($user !== null, 'Membership::user must be eager-loaded before resource serialization.');

        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'joined_at' => $this->created_at->toIso8601String(),
        ];
    }
}
