<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Infrastructure\Http;

use App\Modules\Integrations\Domain\Exceptions\UnsafeWebhookTarget;

/**
 * Validates outbound webhook URLs against SSRF attack patterns.
 *
 *   - Only http and https schemes are allowed.
 *   - The hostname is resolved via DNS and EVERY returned A/AAAA address
 *     must be a public, routable address.
 *   - RFC1918, loopback (127/8, ::1), link-local (169.254/16, fe80::/10),
 *     and IPv6 unique-local (fc00::/7) are rejected.
 *
 * This guard does not protect against DNS rebinding (resolve-public-then-
 * resolve-private between this check and the actual fetch). For higher
 * assurance, pin the IP and pass it to the HTTP client instead of the
 * hostname.
 */
final class UrlGuard
{
    public static function assertSafe(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw UnsafeWebhookTarget::badScheme($parts['scheme'] ?? '(none)');
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], strict: true)) {
            throw UnsafeWebhookTarget::badScheme($scheme);
        }

        $host = $parts['host'];

        // If host is already an IP, validate it directly.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            self::assertPublicAddress($host, $host);

            return;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            throw UnsafeWebhookTarget::unresolvableHost($host);
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (! is_string($ip)) {
                continue;
            }
            self::assertPublicAddress($host, $ip);
        }
    }

    private static function assertPublicAddress(string $host, string $ip): void
    {
        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        if ($isPublic === false) {
            throw UnsafeWebhookTarget::privateAddress($host, $ip);
        }
    }
}
