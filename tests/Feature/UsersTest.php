<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;

it('updates the authenticated user profile', function (): void {
    $user = User::factory()->create(['name' => 'Old', 'email' => 'old@example.test']);

    $this->actingAs($user)
        ->patchJson('/api/v1/users/me', ['name' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New');

    expect($user->refresh()->name)->toBe('New');
});

it('lists only members of the current tenant', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'alpha']);
    $tenantB = Tenant::factory()->create(['slug' => 'beta']);

    $alice = User::factory()->create(['email' => 'alice@example.test']);
    $bob = User::factory()->create(['email' => 'bob@example.test']);
    $carol = User::factory()->create(['email' => 'carol@example.test']);

    Membership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $alice->id, 'role' => Role::Owner->value]);
    Membership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $bob->id, 'role' => Role::Member->value]);
    Membership::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $carol->id, 'role' => Role::Owner->value]);

    $response = $this->actingAs($alice)
        ->withHeader('X-Tenant', 'alpha')
        ->getJson('/api/v1/members')
        ->assertOk();

    $emails = collect($response->json('data'))->pluck('user.email')->all();

    expect($emails)->toHaveCount(2)
        ->and($emails)->toContain('alice@example.test', 'bob@example.test')
        ->and($emails)->not->toContain('carol@example.test');
});

it('allows an owner to promote a member to admin', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'gamma']);
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => Role::Owner->value]);
    $memberMembership = Membership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $member->id,
        'role' => Role::Member->value,
    ]);

    $this->actingAs($owner)
        ->withHeader('X-Tenant', 'gamma')
        ->patchJson("/api/v1/members/{$memberMembership->id}", ['role' => 'admin'])
        ->assertOk()
        ->assertJsonPath('data.role', 'admin');
});

it('forbids a viewer from changing roles', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'delta']);
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $viewer->id, 'role' => Role::Viewer->value]);
    $target = Membership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $other->id,
        'role' => Role::Member->value,
    ]);

    $this->actingAs($viewer)
        ->withHeader('X-Tenant', 'delta')
        ->patchJson("/api/v1/members/{$target->id}", ['role' => 'admin'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'insufficient_role');
});

it('rejects an Admin trying to grant the Owner role', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'no-admin-promote']);
    $admin = User::factory()->create();
    $member = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $admin->id, 'role' => Role::Admin->value]);
    $memberMembership = Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $member->id, 'role' => Role::Member->value]);

    $this->actingAs($admin)
        ->withHeader('X-Tenant', 'no-admin-promote')
        ->patchJson("/api/v1/members/{$memberMembership->id}", ['role' => 'owner'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'insufficient_role');
});

it('rejects an Admin trying to modify an Owner membership', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'no-admin-touch-owner']);
    $admin = User::factory()->create();
    $owner = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $admin->id, 'role' => Role::Admin->value]);
    $ownerMembership = Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => Role::Owner->value]);

    $this->actingAs($admin)
        ->withHeader('X-Tenant', 'no-admin-touch-owner')
        ->patchJson("/api/v1/members/{$ownerMembership->id}", ['role' => 'member'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'insufficient_role');
});

it('rejects any actor trying to modify their own role', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'no-self-modify']);
    $owner = User::factory()->create();
    $secondOwner = User::factory()->create();
    $ownerMembership = Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => Role::Owner->value]);
    Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $secondOwner->id, 'role' => Role::Owner->value]);

    $this->actingAs($owner)
        ->withHeader('X-Tenant', 'no-self-modify')
        ->patchJson("/api/v1/members/{$ownerMembership->id}", ['role' => 'admin'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'cannot_modify_self');
});

it('allows an Owner to promote a Member to Owner (only Owners can grant Owner)', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'owner-grants']);
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => Role::Owner->value]);
    $memberMembership = Membership::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $member->id, 'role' => Role::Member->value]);

    $this->actingAs($owner)
        ->withHeader('X-Tenant', 'owner-grants')
        ->patchJson("/api/v1/members/{$memberMembership->id}", ['role' => 'owner'])
        ->assertOk()
        ->assertJsonPath('data.role', 'owner');
});
