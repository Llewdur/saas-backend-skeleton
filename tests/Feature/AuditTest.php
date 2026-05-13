<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Core\Domain\Models\Project;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;

it('logs activity on project create/update and returns it scoped to tenant', function (): void {
    $tenantA = Tenant::factory()->create(['slug' => 'audit-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'audit-b']);

    $userA = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $userA->id, 'role' => Role::Owner->value]);

    // Create a project for tenant A — should generate an activity log entry.
    $this->actingAs($userA)
        ->withHeader('X-Tenant', 'audit-a')
        ->postJson('/api/v1/projects', ['name' => 'Audited project'])
        ->assertCreated();

    // Create a project for tenant B as a different user.
    $userB = User::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $userB->id, 'role' => Role::Owner->value]);
    $this->actingAs($userB)
        ->withHeader('X-Tenant', 'audit-b')
        ->postJson('/api/v1/projects', ['name' => 'Other tenant project'])
        ->assertCreated();

    // List audit for tenant A — should only see its own activity.
    $response = $this->actingAs($userA)
        ->withHeader('X-Tenant', 'audit-a')
        ->getJson('/api/v1/audit')
        ->assertOk();

    $subjectTypes = collect($response->json('data'))->pluck('subject_type')->unique()->all();

    expect($response->json('data'))->not->toBeEmpty()
        ->and($subjectTypes)->each->toBeIn([
            'App\\Modules\\Tenant\\Domain\\Models\\Tenant',
            'App\\Modules\\Core\\Domain\\Models\\Project',
        ]);

    // Verify no Project entries from tenant B leaked into the response.
    $projectIds = collect($response->json('data'))
        ->filter(fn (array $a): bool => $a['subject_type'] === 'App\\Modules\\Core\\Domain\\Models\\Project')
        ->pluck('subject_id')
        ->all();

    $tenantBProjectIds = Project::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $tenantB->id)
        ->pluck('id')
        ->all();

    expect(array_intersect($projectIds, $tenantBProjectIds))->toBeEmpty();
});
