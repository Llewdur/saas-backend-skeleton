<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Application\UseCases\ListMembers;
use App\Modules\Users\Http\Resources\MemberResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListMembersController
{
    public function __invoke(ListMembers $useCase): AnonymousResourceCollection
    {
        return MemberResource::collection($useCase->execute());
    }
}
