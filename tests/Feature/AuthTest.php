<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\Models\Tenant;

it('registers a user with a personal tenant and owner membership atomically', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'password' => 'analytical-engine',
        'password_confirmation' => 'analytical-engine',
        'tenant_name' => 'Analytical Engine Co',
    ])->assertCreated();

    $response->assertJsonPath('data.email', 'ada@example.test');

    $user = User::query()->where('email', 'ada@example.test')->firstOrFail();
    $tenant = Tenant::query()->where('name', 'Analytical Engine Co')->firstOrFail();

    expect($user->memberships()->count())->toBe(1)
        ->and($user->memberships()->first()->tenant_id)->toBe($tenant->id)
        ->and($user->memberships()->first()->role)->toBe(Role::Owner);
});

it('rejects registration with a duplicate email', function (): void {
    User::factory()->create(['email' => 'existing@example.test']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Someone',
        'email' => 'existing@example.test',
        'password' => 'pass-phrase-1',
        'password_confirmation' => 'pass-phrase-1',
        'tenant_name' => 'Org',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('logs in and returns a Sanctum token', function (): void {
    User::factory()->create([
        'email' => 'login@example.test',
        'password' => bcrypt('correct-horse-battery-staple'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@example.test',
        'password' => 'correct-horse-battery-staple',
        'device_name' => 'pest',
    ])->assertOk();

    expect($response->json('data.token'))->toBeString()
        ->and(strlen((string) $response->json('data.token')))->toBeGreaterThan(20);
});

it('rejects login with bad credentials', function (): void {
    User::factory()->create([
        'email' => 'login2@example.test',
        'password' => bcrypt('the-right-password'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'login2@example.test',
        'password' => 'the-wrong-password',
    ])->assertStatus(401)
        ->assertJsonPath('errors.0.code', 'invalid_credentials');
});

it('returns the current user via me when authenticated', function (): void {
    $user = User::factory()->create(['email' => 'me@example.test']);

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'me@example.test');
});

it('revokes the current token on logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('pest');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

it('returns 400 on logout when no bearer token is present', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/auth/logout')
        ->assertStatus(400)
        ->assertJsonPath('errors.0.code', 'no_bearer_token');
});

it('issues a Sanctum token with a tenant ability per membership', function (): void {
    $user = User::factory()->create([
        'email' => 'multi@example.test',
        'password' => bcrypt('multi-pass-12345'),
    ]);
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    Membership::factory()->create(['tenant_id' => $tenantA->id, 'user_id' => $user->id, 'role' => Role::Owner->value]);
    Membership::factory()->create(['tenant_id' => $tenantB->id, 'user_id' => $user->id, 'role' => Role::Member->value]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'multi@example.test',
        'password' => 'multi-pass-12345',
    ])->assertOk();

    // Latest token on this user should hold tenant:{A} AND tenant:{B}.
    $abilities = $user->tokens()->latest('id')->first()->abilities;

    expect($abilities)->toContain("tenant:{$tenantA->id}")
        ->and($abilities)->toContain("tenant:{$tenantB->id}");
});
