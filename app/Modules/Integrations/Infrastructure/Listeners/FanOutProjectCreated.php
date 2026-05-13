<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Infrastructure\Listeners;

use App\Modules\Core\Domain\Events\ProjectCreated;
use App\Modules\Integrations\Domain\Models\Webhook;
use App\Modules\Integrations\Infrastructure\Jobs\DispatchOutboundWebhook;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class FanOutProjectCreated implements ShouldQueueAfterCommit
{
    private const EVENT = 'project.created';

    public function handle(ProjectCreated $event): void
    {
        $webhooks = Webhook::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $event->project->tenant_id)
            ->whereNull('disabled_at')
            ->get()
            ->filter(fn (Webhook $w): bool => $w->subscribesTo(self::EVENT));

        foreach ($webhooks as $webhook) {
            DispatchOutboundWebhook::dispatch(
                webhookId: $webhook->id,
                event: self::EVENT,
                payload: [
                    'id' => $event->project->id,
                    'name' => $event->project->name,
                    'description' => $event->project->description,
                ],
            );
        }
    }
}
