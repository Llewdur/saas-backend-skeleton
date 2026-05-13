<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Concerns;

use App\Modules\Tenant\Domain\Exceptions\CrossTenantAccessAttempted;
use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Domain\TenantContext;
use App\Modules\Tenant\Domain\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for tenant-owned Eloquent models.
 *
 * Three independent enforcement points:
 *   1. Global scope: every query filters by current tenant_id (when a tenant
 *      context exists).
 *   2. `creating` hook: auto-fills tenant_id from context when not supplied —
 *      paired with $fillable excluding tenant_id, this prevents a request
 *      payload from setting a foreign tenant_id.
 *   3. `updating` hook: tenant_id is immutable once set. A row cannot migrate
 *      across tenants under any circumstance.
 *
 * Models using this trait MUST have a `tenant_id` column AND MUST exclude
 * `tenant_id` from $fillable.
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

        static::updating(function (Model $model): void {
            if (! $model->isDirty('tenant_id')) {
                return;
            }

            $original = new TenantId((int) $model->getOriginal('tenant_id'));
            $new = new TenantId((int) $model->getAttribute('tenant_id'));

            throw CrossTenantAccessAttempted::with($original, $new);
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
