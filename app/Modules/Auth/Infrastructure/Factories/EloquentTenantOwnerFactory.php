<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Factories;

use App\Models\User;
use App\Modules\Auth\Application\DTOs\RegisterUserInput;
use App\Modules\Auth\Application\DTOs\TenantOwnerCreated;
use App\Modules\Auth\Domain\Factories\TenantOwnerFactory;
use App\Modules\Tenant\Domain\Enums\Plan;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Domain\ValueObjects\Slug;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class EloquentTenantOwnerFactory implements TenantOwnerFactory
{
    public function create(RegisterUserInput $input): TenantOwnerCreated
    {
        $user = new User;
        $user->name = $input->name;
        $user->email = $input->email;
        $user->password = Hash::make($input->password);
        $user->save();

        $tenant = new Tenant;
        $tenant->name = $input->tenantName;
        $tenant->slug = (string) Slug::fromName($input->tenantName).'-'.Str::lower(Str::random(6));
        $tenant->plan = Plan::Free;
        $tenant->save();

        // tenant_id is intentionally NOT in Membership::$fillable, so we set
        // it via attribute access to honour the BelongsToTenant invariant
        // ("payload-supplied tenant_id never reaches Eloquent").
        $membership = new Membership;
        $membership->tenant_id = $tenant->id;
        $membership->user_id = $user->id;
        $membership->role = Role::Owner;
        $membership->save();

        return new TenantOwnerCreated($user, $tenant, $membership);
    }
}
