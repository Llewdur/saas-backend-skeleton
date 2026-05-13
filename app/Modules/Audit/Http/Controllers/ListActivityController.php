<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Modules\Audit\Application\UseCases\ListRecentActivity;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

final class ListActivityController
{
    public function __invoke(ListRecentActivity $useCase): JsonResponse
    {
        $activities = $useCase->execute();

        return new JsonResponse([
            'data' => $activities->map(fn (Activity $a): array => [
                'id' => $a->id,
                'log_name' => $a->log_name,
                'description' => $a->description,
                'subject_type' => $a->subject_type,
                'subject_id' => $a->subject_id,
                'properties' => $a->properties?->toArray() ?? [],
                'created_at' => $a->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
