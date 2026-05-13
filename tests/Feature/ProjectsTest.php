<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Core\Domain\Models\Project;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Exceptions\CrossTenantAccessAttempted;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;

function asMemberOf(Tenant $tenant, Role $role = Role::Member): User
{
    $user = User::factory()->create();
    Membership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => $role->value,
    ]);

    return $user;
}

it('creates a project scoped to the current tenant', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'acme']);
    $user = asMemberOf($tenant, Role::Owner);

    $response = $this->actingAs($user)
        ->withHeader('X-Tenant', 'acme')
        ->postJson('/api/v1/projects', [
            'name' => 'Build the future',
            'description' => 'Now with footnotes.',
        ])->assertCreated();

    $response->assertJsonPath('data.name', 'Build the future')
        ->assertJsonPath('data.tenant_id', $tenant->id);
});

it('lists only projects in the current tenant', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'alpha']);
    $tenantB = Tenant::factory()->create(['slug' => 'beta']);

    Project::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'In Alpha']);
    Project::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Also Alpha']);
    Project::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'In Beta']);

    $user = asMemberOf($tenantA, Role::Member);

    $response = $this->actingAs($user)
        ->withHeader('X-Tenant', 'alpha')
        ->getJson('/api/v1/projects')
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name')->all();

    expect($names)->toHaveCount(2)
        ->and($names)->toContain('In Alpha', 'Also Alpha')
        ->and($names)->not->toContain('In Beta');
});

it('cannot view a project from another tenant', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'alpha2']);
    $tenantB = Tenant::factory()->create(['slug' => 'beta2']);

    $foreignProject = Project::factory()->create(['tenant_id' => $tenantB->id]);
    $user = asMemberOf($tenantA);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'alpha2')
        ->getJson("/api/v1/projects/{$foreignProject->id}")
        ->assertNotFound();
});

it('cannot delete a project from another tenant', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'alpha3']);
    $tenantB = Tenant::factory()->create(['slug' => 'beta3']);

    $foreignProject = Project::factory()->create(['tenant_id' => $tenantB->id]);
    $user = asMemberOf($tenantA, Role::Owner);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'alpha3')
        ->deleteJson("/api/v1/projects/{$foreignProject->id}")
        ->assertNotFound();

    // Foreign project still exists.
    expect(Project::withoutGlobalScopes()->find($foreignProject->id))->not->toBeNull();
});

it('refuses to create a project for a Viewer', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'viewer-tenant']);
    $user = asMemberOf($tenant, Role::Viewer);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'viewer-tenant')
        ->postJson('/api/v1/projects', ['name' => 'should fail'])
        ->assertForbidden();
});

it('updates a project name without touching description', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'omega']);
    $project = Project::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old name',
        'description' => 'Keep me.',
    ]);
    $user = asMemberOf($tenant, Role::Member);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'omega')
        ->patchJson("/api/v1/projects/{$project->id}", ['name' => 'New name'])
        ->assertOk();

    $project->refresh();
    expect($project->name)->toBe('New name')
        ->and($project->description)->toBe('Keep me.');
});

it('blocks forged tenant_id in the request payload', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'alpha4']);
    $tenantB = Tenant::factory()->create(['slug' => 'beta4']);
    $user = asMemberOf($tenantA, Role::Owner);

    $this->actingAs($user)
        ->withHeader('X-Tenant', 'alpha4')
        ->postJson('/api/v1/projects', [
            'name' => 'Should belong to alpha',
            'tenant_id' => $tenantB->id, // forged
        ])->assertCreated();

    $project = Project::withoutGlobalScopes()
        ->where('name', 'Should belong to alpha')
        ->firstOrFail();

    expect($project->tenant_id)->toBe($tenantA->id);
});

it('rejects mutating a project tenant_id after creation (row migration)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenantA->id]);

    $project->tenant_id = $tenantB->id;

    expect(fn () => $project->save())->toThrow(CrossTenantAccessAttempted::class);

    expect(Project::withoutGlobalScopes()->find($project->id)->tenant_id)->toBe($tenantA->id);
});
