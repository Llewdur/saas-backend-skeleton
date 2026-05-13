<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Support\Jitter;
use LogicException;

/**
 * For queued jobs that hit an external system. Provides a backoff() method
 * that returns a decorrelated-jitter draw around an exponential base.
 *
 * Implementing class must declare:
 *
 *   /\** @var array<int, int> *\/
 *   private const BACKOFF_BASE_SECONDS = [30, 60, 300, 900, 3600];
 *
 * Per CODING_STANDARDS.md §16, every external-system job is required to
 * use jittered backoff so failed retries don't synchronise.
 */
trait HasJitteredBackoff
{
    public function backoff(): int
    {
        $base = static::BACKOFF_BASE_SECONDS;

        // @phpstan-ignore identical.alwaysFalse
        if ($base === []) {
            // PHPStan can prove this branch is unreachable for the current
            // consumer (DispatchOutboundWebhook declares 5 entries). The
            // check is here for future implementers — an empty array would
            // collapse retries to 0s and produce the thundering herd this
            // trait exists to prevent.
            throw new LogicException(
                static::class.'::BACKOFF_BASE_SECONDS is empty; declare at least one base delay.',
            );
        }

        $attempt = max(1, $this->attempts()) - 1;
        $delay = $base[$attempt] ?? end($base);

        return Jitter::decorrelate((int) $delay);
    }
}
