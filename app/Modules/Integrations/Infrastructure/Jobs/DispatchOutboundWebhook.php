<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Infrastructure\Jobs;

use App\Modules\Integrations\Domain\Exceptions\UnsafeWebhookTarget;
use App\Modules\Integrations\Domain\Models\Webhook;
use App\Modules\Integrations\Infrastructure\Http\UrlGuard;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Delivers a single webhook event to one subscriber URL.
 *
 *   - Idempotent at the queue level: ShouldBeUnique with a key derived from
 *     webhook id + event + payload hash, deduping retries-in-flight within a
 *     60-second window. The X-Delivery-Id header lets receivers dedupe at
 *     their end too.
 *   - SSRF-hardened: UrlGuard rejects non-http(s) schemes and any private,
 *     loopback, or link-local resolved address before the HTTP call.
 *   - No redirect-following — the guard would have to re-run on the
 *     redirect target, which is exactly the kind of bypass we don't want.
 */
final class DispatchOutboundWebhook implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 300, 900, 3600];

    public int $timeout = 15;

    public int $uniqueFor = 60;

    private readonly string $deliveryId;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly int $webhookId,
        private readonly string $event,
        private readonly array $payload,
    ) {
        $this->deliveryId = (string) Str::ulid();
    }

    public function uniqueId(): string
    {
        return "wh:{$this->webhookId}:{$this->event}:".hash(
            'sha256',
            (string) json_encode($this->payload),
        );
    }

    public function handle(): void
    {
        $webhook = Webhook::query()->withoutGlobalScopes()->find($this->webhookId);
        if ($webhook === null || ! $webhook->isActive()) {
            return;
        }

        try {
            UrlGuard::assertSafe($webhook->url);
        } catch (UnsafeWebhookTarget $e) {
            // Disable the webhook so we don't keep retrying an unsafe target.
            $webhook->disabled_at = now();
            $webhook->save();
            throw $e;
        }

        $body = json_encode([
            'event' => $this->event,
            'tenant_id' => $webhook->tenant_id,
            'delivery_id' => $this->deliveryId,
            'payload' => $this->payload,
            'sent_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $body, $webhook->secret);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Signature' => 'sha256='.$signature,
            'X-Event' => $this->event,
            'X-Delivery-Id' => $this->deliveryId,
        ])->withBody($body, 'application/json')
            ->timeout($this->timeout)
            ->withOptions(['allow_redirects' => false])
            ->post($webhook->url)
            ->throw();
    }

    public function failed(Throwable $exception): void
    {
        // Surface to logs; idempotency at the delivery-id level means re-runs
        // (after the unique window expires) are safe.
    }
}
