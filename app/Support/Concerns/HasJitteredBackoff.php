<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Support\Jitter;

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
        $attempt = max(1, $this->attempts()) - 1;
        $delay = $base[$attempt] ?? end($base);

        return Jitter::decorrelate((int) $delay);
    }
}
