<?php

declare(strict_types=1);

use App\Modules\Core\Domain\Events\ProjectCreated;
use App\Modules\Core\Domain\Models\Project;
use App\Modules\Integrations\Domain\Models\Webhook;
use App\Modules\Integrations\Infrastructure\Jobs\DispatchOutboundWebhook;
use App\Modules\Integrations\Infrastructure\Listeners\FanOutProjectCreated;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Support\Facades\Queue;

it('fans out to subscribed webhooks when a project is created', function (): void {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $webhook = Webhook::factory()->create([
        'tenant_id' => $tenant->id,
        'events' => ['project.created'],
    ]);
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    (new FanOutProjectCreated)->handle(new ProjectCreated($project));

    Queue::assertPushed(DispatchOutboundWebhook::class, 1);
});

it('skips disabled webhooks and webhooks not subscribed to the event', function (): void {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    Webhook::factory()->create([
        'tenant_id' => $tenant->id,
        'events' => ['project.created'],
        'disabled_at' => now(),
    ]);
    Webhook::factory()->create([
        'tenant_id' => $tenant->id,
        'events' => ['user.invited'],
    ]);
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    (new FanOutProjectCreated)->handle(new ProjectCreated($project));

    Queue::assertNotPushed(DispatchOutboundWebhook::class);
});

it('isolates webhook fan-out by tenant', function (): void {
    Queue::fake();

    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Webhook::factory()->create([
        'tenant_id' => $tenantB->id,
        'events' => ['project.created'],
    ]);

    $projectInA = Project::factory()->create(['tenant_id' => $tenantA->id]);

    (new FanOutProjectCreated)->handle(new ProjectCreated($projectInA));

    Queue::assertNotPushed(DispatchOutboundWebhook::class);
});
