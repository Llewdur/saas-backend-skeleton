<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Infrastructure\Persistence\TenantContext;

it('binds the tenant from X-Tenant header by slug', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'acme']);
    $user = User::factory()->create();
    Membership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => Role::Owner->value,
    ]);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'acme')
        ->getJson('/api/user')
        ->assertOk();

    expect(app(TenantContext::class)->has())->toBeTrue()
        ->and(app(TenantContext::class)->get()->slug)->toBe('acme');
});

it('falls back to the authenticated user first membership when no header', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'first']);
    $tenantB = Tenant::factory()->create(['slug' => 'second']);
    $user = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $user->id]);
    Membership::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $user->id]);

    $this->actingAs($user)->getJson('/api/user')->assertOk();

    expect(app(TenantContext::class)->get()->id)->toBe($tenantA->id);
});

it('leaves the tenant context empty when no resolution succeeds', function (): void {
    $this->getJson('/api/user')->assertUnauthorized();

    expect(app(TenantContext::class)->has())->toBeFalse();
});
