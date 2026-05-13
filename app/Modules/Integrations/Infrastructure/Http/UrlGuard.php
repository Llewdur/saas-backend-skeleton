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
 *   - The first validated IP is returned so the caller can pin it to the
 *     HTTP client via CURLOPT_RESOLVE — without that pin, the DNS query
 *     during the actual fetch could return a different (private) IP
 *     between this check and the request (DNS rebinding).
 */
final class UrlGuard
{
    /**
     * Validates the URL and returns the IP that the request should resolve
     * to. Pass this IP to the HTTP client (CURLOPT_RESOLVE) so the actual
     * call uses the same address the guard validated — closes the DNS
     * rebinding window between assertSafe() and the HTTP call.
     *
     * If the URL already specified a literal IP, that IP is returned.
     */
    public static function assertSafe(string $url): string
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

        // Host is already a literal IP.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            self::assertPublicAddress($host, $host);

            return $host;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            throw UnsafeWebhookTarget::unresolvableHost($host);
        }

        $firstIp = null;
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (! is_string($ip)) {
                continue;
            }
            self::assertPublicAddress($host, $ip);
            $firstIp ??= $ip;
        }

        if ($firstIp === null) {
            throw UnsafeWebhookTarget::unresolvableHost($host);
        }

        return $firstIp;
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
