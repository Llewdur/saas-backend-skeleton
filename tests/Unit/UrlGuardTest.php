<?php

declare(strict_types=1);

use App\Modules\Integrations\Domain\Exceptions\UnsafeWebhookTarget;
use App\Modules\Integrations\Infrastructure\Http\UrlGuard;

it('accepts a literal public IP', function (): void {
    UrlGuard::assertSafe('https://8.8.8.8/webhook');
})->throwsNoExceptions();

it('rejects non-http(s) schemes', function (string $url): void {
    expect(fn () => UrlGuard::assertSafe($url))->toThrow(UnsafeWebhookTarget::class);
})->with([
    'file:///etc/passwd',
    'ftp://example.com/x',
    'gopher://example.com',
    'data:text/plain,hello',
    'javascript:alert(1)',
]);

it('rejects literal private IPv4 addresses', function (string $url): void {
    expect(fn () => UrlGuard::assertSafe($url))->toThrow(UnsafeWebhookTarget::class);
})->with([
    'http://127.0.0.1/x',         // loopback
    'http://10.0.0.1/x',          // RFC1918
    'http://192.168.1.1/x',       // RFC1918
    'http://172.16.0.1/x',        // RFC1918
    'http://169.254.169.254/x',   // link-local (AWS / GCP metadata)
    'http://0.0.0.0/x',           // unspecified
]);

it('rejects literal IPv6 loopback and link-local addresses', function (string $url): void {
    expect(fn () => UrlGuard::assertSafe($url))->toThrow(UnsafeWebhookTarget::class);
})->with([
    'http://[::1]/x',             // IPv6 loopback
    'http://[fe80::1]/x',         // link-local
    'http://[fc00::1]/x',         // IPv6 ULA
]);

it('rejects malformed URLs', function (): void {
    expect(fn () => UrlGuard::assertSafe('not a url at all'))
        ->toThrow(UnsafeWebhookTarget::class);
});
