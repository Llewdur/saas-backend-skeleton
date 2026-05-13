<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain;

use App\Modules\Tenant\Domain\Exceptions\TenantContextMissing;
use App\Modules\Tenant\Domain\Models\Tenant;

/**
 * Container-bound singleton holding the current tenant for the request.
 * Set by ResolveTenant middleware; read by BelongsToTenant trait + policies.
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

    public function id(): int
    {
        return $this->get()->id;
    }
}
