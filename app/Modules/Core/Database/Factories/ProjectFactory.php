<?php

declare(strict_types=1);

namespace App\Modules\Core\Database\Factories;

use App\Modules\Core\Domain\Models\Project;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->boolean() ? $this->faker->sentence() : null,
        ];
    }
}
