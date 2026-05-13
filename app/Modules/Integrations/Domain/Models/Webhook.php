<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Domain\Models;

use App\Modules\Integrations\Database\Factories\WebhookFactory;
use App\Modules\Tenant\Infrastructure\Persistence\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $url
 * @property array<int, string> $events
 * @property string $secret
 * @property ?Carbon $disabled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = ['url', 'events', 'secret', 'disabled_at'];

    protected $casts = [
        'events' => 'array',
        'disabled_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->disabled_at === null;
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events, strict: true);
    }

    protected static function newFactory(): WebhookFactory
    {
        return WebhookFactory::new();
    }
}
