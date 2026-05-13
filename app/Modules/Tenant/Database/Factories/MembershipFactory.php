<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Database\Factories;

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
final class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'role' => Role::Member->value,
        ];
    }

    public function owner(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => Role::Owner->value,
        ]);
    }
}
