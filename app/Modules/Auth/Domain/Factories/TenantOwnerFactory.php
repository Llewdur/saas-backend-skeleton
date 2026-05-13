<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Factories;

use App\Modules\Auth\Application\DTOs\RegisterUserInput;
use App\Modules\Auth\Application\DTOs\TenantOwnerCreated;

/**
 * Creates the canonical aggregate for a new tenant: a User row, a Tenant row,
 * and an owner Membership row connecting them.
 *
 * Lives as an interface so:
 *   - the use case can hold zero Eloquent (matches §8 spirit applied to use cases)
 *   - tests can fake the factory without touching the DB
 *   - the writes are isolated to a single class that's easy to audit for
 *     the BelongsToTenant `$fillable`-exclusion invariant
 */
interface TenantOwnerFactory
{
    public function create(RegisterUserInput $input): TenantOwnerCreated;
}
