<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Domain\Exceptions;

use DomainException;

final class UnsafeWebhookTarget extends DomainException
{
    public static function badScheme(string $scheme): self
    {
        return new self("Webhook URL scheme must be http or https, got '{$scheme}'.");
    }

    public static function unresolvableHost(string $host): self
    {
        return new self("Webhook URL host '{$host}' could not be resolved.");
    }

    public static function privateAddress(string $host, string $ip): self
    {
        return new self("Webhook URL host '{$host}' resolves to private/loopback/link-local address '{$ip}'.");
    }
}
