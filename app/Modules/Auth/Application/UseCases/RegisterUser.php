<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\UseCases;

use App\Models\User;
use App\Modules\Auth\Application\DTOs\RegisterUserInput;
use App\Modules\Auth\Domain\Events\UserRegistered;
use App\Modules\Tenant\Domain\Enums\Plan;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Events\TenantCreated;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Domain\ValueObjects\Slug;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;

final class RegisterUser
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
    ) {}

    public function execute(RegisterUserInput $input): User
    {
        return $this->db->transaction(function () use ($input): User {
            $user = User::query()->create([
                'name' => $input->name,
                'email' => $input->email,
                'password' => Hash::make($input->password),
            ]);

            $tenant = Tenant::query()->create([
                'name' => $input->tenantName,
                'slug' => Slug::fromName($input->tenantName).'-'.substr((string) $user->id, 0, 6),
                'plan' => Plan::Free->value,
            ]);

            Membership::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => Role::Owner->value,
            ]);

            $this->events->dispatch(new TenantCreated($tenant));
            $this->events->dispatch(new UserRegistered($user, $tenant));

            return $user;
        });
    }
}
