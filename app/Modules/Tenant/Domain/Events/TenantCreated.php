<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Events;

use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class TenantCreated
{
    use Dispatchable;

    public function __construct(public Tenant $tenant) {}
}
