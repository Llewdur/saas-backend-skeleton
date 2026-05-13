<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Modules\Tenant\Infrastructure\Persistence\TenantContext;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

final class ListActivityController
{
    public function __invoke(TenantContext $context): JsonResponse
    {
        $tenantId = $context->id();

        $activities = Activity::query()
            ->where(function ($query) use ($tenantId): void {
                // Tenant model's own activity.
                $query->where(function ($q) use ($tenantId): void {
                    $q->where('subject_type', 'App\\Modules\\Tenant\\Domain\\Models\\Tenant')
                        ->where('subject_id', $tenantId);
                })
                // Plus any project's activity scoped to this tenant.
                ->orWhere(function ($q) use ($tenantId): void {
                    $q->where('subject_type', 'App\\Modules\\Core\\Domain\\Models\\Project')
                        ->whereIn('subject_id', function ($sub) use ($tenantId): void {
                            $sub->select('id')->from('projects')->where('tenant_id', $tenantId);
                        });
                });
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get();

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
