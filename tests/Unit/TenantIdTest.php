<?php

declare(strict_types=1);

use App\Modules\Tenant\Domain\ValueObjects\TenantId;

it('constructs with a positive integer', function (): void {
    $id = new TenantId(42);

    expect($id->value)->toBe(42)
        ->and((string) $id)->toBe('42');
});

it('rejects zero or negative integers', function (int $invalid): void {
    expect(fn () => new TenantId($invalid))->toThrow(InvalidArgumentException::class);
})->with([0, -1, -100]);

it('compares by value, not identity', function (): void {
    $a = new TenantId(7);
    $b = new TenantId(7);
    $c = new TenantId(8);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
