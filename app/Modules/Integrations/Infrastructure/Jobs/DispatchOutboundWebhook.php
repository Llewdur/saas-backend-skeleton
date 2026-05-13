<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Infrastructure\Jobs;

use App\Modules\Integrations\Domain\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

final class DispatchOutboundWebhook implements ShouldQueue
{
    use FoundationQueueable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 300, 900, 3600];

    public int $timeout = 15;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly int $webhookId,
        private readonly string $event,
        private readonly array $payload,
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::query()->withoutGlobalScopes()->find($this->webhookId);
        if ($webhook === null || ! $webhook->isActive()) {
            return;
        }

        $body = json_encode([
            'event' => $this->event,
            'tenant_id' => $webhook->tenant_id,
            'payload' => $this->payload,
            'sent_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $body, $webhook->secret);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Signature' => 'sha256='.$signature,
            'X-Event' => $this->event,
        ])->withBody($body, 'application/json')
            ->timeout($this->timeout)
            ->post($webhook->url)
            ->throw();
    }

    public function failed(Throwable $exception): void
    {
        // Surface to logs; idempotency means re-runs are safe.
    }
}
