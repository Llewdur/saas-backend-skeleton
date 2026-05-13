<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Users\Http\Resources\MemberResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListMembersController
{
    public function __invoke(): AnonymousResourceCollection
    {
        $memberships = Membership::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        return MemberResource::collection($memberships);
    }
}
