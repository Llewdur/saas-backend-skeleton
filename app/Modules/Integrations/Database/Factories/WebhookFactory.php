<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Database\Factories;

use App\Modules\Integrations\Domain\Models\Webhook;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Webhook>
 */
final class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'url' => $this->faker->url(),
            'events' => ['project.created'],
            'secret' => Str::random(48),
            'disabled_at' => null,
        ];
    }
}
