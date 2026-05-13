<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Database\Factories;

use App\Modules\Tenant\Domain\Enums\Plan;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'plan' => Plan::Free->value,
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => Plan::Pro->value,
        ]);
    }
}
