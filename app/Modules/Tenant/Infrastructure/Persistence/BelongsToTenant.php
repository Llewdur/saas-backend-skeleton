<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Infrastructure\Persistence;

use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for tenant-owned Eloquent models.
 *
 * Adds: global scope filtering by current tenant, auto-fill of tenant_id on
 * create, and a tenant() relationship. The current tenant comes from the
 * container-bound TenantContext singleton.
 *
 * Models using this trait MUST have a `tenant_id` column.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $context = app(TenantContext::class);
            if ($context->has()) {
                $query->where($query->getModel()->getTable().'.tenant_id', $context->id());
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $context = app(TenantContext::class);
            if ($context->has()) {
                $model->setAttribute('tenant_id', $context->id());
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
