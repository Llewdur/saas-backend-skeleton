<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Adds randomness to retry / poll delays so multiple processes don't
 * synchronise on the same wall-clock instants (thundering herd).
 *
 * Decorrelated jitter is the AWS-recommended variant for retries hitting
 * an external system: random draw within ±$factor of the base delay.
 */
final class Jitter
{
    /**
     * Returns an integer within [base * (1 - $factor), base * (1 + $factor)].
     *
     * Example: Jitter::decorrelate(100) yields a value in [75, 125].
     */
    public static function decorrelate(int $base, float $factor = 0.25): int
    {
        $low = (int) ($base * (1 - $factor));
        $high = (int) ($base * (1 + $factor));

        return random_int($low, max($high, $low));
    }
}
