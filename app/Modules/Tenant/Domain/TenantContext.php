<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain;

use App\Modules\Tenant\Domain\Exceptions\TenantContextMissing;
use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Domain\ValueObjects\TenantId;

/**
 * Container-bound, request-scoped holder of the current tenant.
 *
 * Set by ResolveTenant middleware; read by BelongsToTenant trait, policies,
 * and use cases.
 *
 * Two read methods exist on purpose:
 *   - tenantId(): TenantId — for domain-layer code and module-boundary calls
 *     (typed-ID enforcement per CODING_STANDARDS.md §6).
 *   - id(): int — for framework touchpoints (query builders, route binding)
 *     that need the raw int. Returns $this->tenantId()->value.
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function get(): Tenant
    {
        if ($this->tenant === null) {
            throw TenantContextMissing::forOperation('read tenant');
        }

        return $this->tenant;
    }

    public function tenantId(): TenantId
    {
        return new TenantId($this->get()->id);
    }

    public function id(): int
    {
        return $this->tenantId()->value;
    }
}
