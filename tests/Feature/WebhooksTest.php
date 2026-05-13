<?php

declare(strict_types=1);

use App\Modules\Core\Domain\Events\ProjectCreated;
use App\Modules\Core\Domain\Models\Project;
use App\Modules\Integrations\Domain\Exceptions\UnsafeWebhookTarget;
use App\Modules\Integrations\Domain\Models\Webhook;
use App\Modules\Integrations\Infrastructure\Jobs\DispatchOutboundWebhook;
use App\Modules\Integrations\Infrastructure\Listeners\FanOutProjectCreated;
use App\Modules\Tenant\Domain\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Throwable;

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

it('dispatches with HMAC signature and a unique delivery id', function (): void {
    Http::fake();
    Http::preventStrayRequests();

    $tenant = Tenant::factory()->create();
    $webhook = Webhook::factory()->create([
        'tenant_id' => $tenant->id,
        'url' => 'https://8.8.8.8/webhook',
        'events' => ['project.created'],
        'secret' => 'shh-test-secret-do-not-use-in-prod',
    ]);

    $job = new DispatchOutboundWebhook(
        webhookId: $webhook->id,
        event: 'project.created',
        payload: ['id' => 42, 'name' => 'X'],
    );
    $job->handle();

    Http::assertSent(function ($request) use ($webhook): bool {
        $body = (string) $request->body();
        $expectedSig = 'sha256='.hash_hmac('sha256', $body, $webhook->secret);

        return $request->url() === 'https://8.8.8.8/webhook'
            && $request->method() === 'POST'
            && $request->header('X-Signature')[0] === $expectedSig
            && $request->header('X-Event')[0] === 'project.created'
            && is_string($request->header('X-Delivery-Id')[0])
            && str_contains($body, '"delivery_id"');
    });
});

it('does not follow redirects', function (): void {
    // The dispatcher's withOptions(['allow_redirects' => false]) tells Guzzle
    // not to chase 3xx responses. Verify by faking a 302 that points to
    // evil.example.test — the dispatcher should send exactly one request
    // (the original POST) and never touch evil.example.test.
    Http::fake([
        'https://8.8.8.8/webhook' => Http::response(null, 302, [
            'Location' => 'https://evil.example.test/',
        ]),
        '*' => Http::response('NOT REACHED', 599),
    ]);

    $tenant = Tenant::factory()->create();
    $webhook = Webhook::factory()->create([
        'tenant_id' => $tenant->id,
        'url' => 'https://8.8.8.8/webhook',
        'events' => ['project.created'],
    ]);

    $job = new DispatchOutboundWebhook($webhook->id, 'project.created', []);

    try {
        $job->handle();
    } catch (Throwable) {
        // ->throw() raises on 302 without redirect-follow; that's expected.
    }

    Http::assertSentCount(1);
    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'evil.example.test'));
});

it('disables the webhook and throws when the target is an unsafe address', function (): void {
    Http::fake(); // never reached

    $tenant = Tenant::factory()->create();
    $webhook = Webhook::factory()->create([
        'tenant_id' => $tenant->id,
        'url' => 'http://169.254.169.254/latest/meta-data/', // AWS metadata IP
        'events' => ['project.created'],
    ]);

    $job = new DispatchOutboundWebhook($webhook->id, 'project.created', ['x' => 1]);

    expect(fn () => $job->handle())->toThrow(UnsafeWebhookTarget::class);

    Http::assertNothingSent();
    expect($webhook->fresh()->isActive())->toBeFalse();
});
