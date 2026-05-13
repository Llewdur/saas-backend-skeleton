<?php

declare(strict_types=1);

use App\Support\Jitter;

it('returns a value within ±25% of the base by default', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $result = Jitter::decorrelate(100);
        expect($result)->toBeGreaterThanOrEqual(75)
            ->and($result)->toBeLessThanOrEqual(125);
    }
});

it('respects a custom factor', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $result = Jitter::decorrelate(1000, 0.5);
        expect($result)->toBeGreaterThanOrEqual(500)
            ->and($result)->toBeLessThanOrEqual(1500);
    }
});

it('produces varied output across many draws (not a constant)', function (): void {
    $values = [];
    for ($i = 0; $i < 50; $i++) {
        $values[] = Jitter::decorrelate(100);
    }
    expect(count(array_unique($values)))->toBeGreaterThan(5);
});
