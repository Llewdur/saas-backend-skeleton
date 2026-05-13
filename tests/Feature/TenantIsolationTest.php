<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;
use App\Modules\Tenant\Domain\TenantContext;

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

it('ignores an X-Tenant header for a tenant the user is not a member of', function (): void {
    $home = Tenant::factory()->create(['slug' => 'home']);
    $foreign = Tenant::factory()->create(['slug' => 'foreign']);
    $user = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $home->id, 'user_id' => $user->id, 'role' => Role::Owner->value]);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'foreign')
        ->getJson('/api/user')
        ->assertOk();

    // Should fall back to the user's home tenant, not the spoofed one.
    expect(app(TenantContext::class)->get()->slug)->toBe('home');
});

it('strips a mass-assigned tenant_id when creating a Membership (BelongsToTenant + $fillable)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    app(TenantContext::class)->set($tenantA);

    // Forge attempt: mass-assign tenant_id of a foreign tenant. Membership's
    // $fillable excludes tenant_id, so the field is stripped; the trait's
    // creating hook then fills it from the context (tenantA).
    Membership::create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'role' => Role::Member->value,
    ]);

    $row = Membership::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();

    expect($row->tenant_id)->toBe($tenantA->id)
        ->and($row->tenant_id)->not->toBe($tenantB->id);
});

it('clears stale tenant context at the start of every request', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'one']);
    $tenantB = Tenant::factory()->create(['slug' => 'two']);
    $userInA = User::factory()->create();
    $userInB = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $userInA->id, 'role' => Role::Owner->value]);
    Membership::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $userInB->id, 'role' => Role::Owner->value]);

    // Request 1 — tenant A.
    $this->actingAs($userInA)
        ->withHeader('X-Tenant', 'one')
        ->getJson('/api/user')->assertOk();

    // Request 2 — tenant B as a different user. Context must NOT leak from #1.
    $this->actingAs($userInB)
        ->withHeader('X-Tenant', 'two')
        ->getJson('/api/user')->assertOk();

    expect(app(TenantContext::class)->get()->slug)->toBe('two');
});
