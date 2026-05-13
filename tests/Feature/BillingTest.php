<?php

declare(strict_types=1);

use App\Models\User;

it('returns free plan when user has no subscription', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/billing/subscription')
        ->assertOk()
        ->assertJsonPath('data.status', 'none')
        ->assertJsonPath('data.plan', 'free');
});

it('requires authentication for the billing endpoint', function (): void {
    $this->getJson('/api/v1/billing/subscription')->assertUnauthorized();
});
