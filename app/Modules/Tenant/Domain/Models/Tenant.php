<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Models;

use App\Modules\Tenant\Database\Factories\TenantFactory;
use App\Modules\Tenant\Domain\Enums\Plan;
use App\Modules\Tenant\Domain\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Plan $plan
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'slug', 'plan'];

    protected $casts = [
        'plan' => Plan::class,
    ];

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function tenantId(): TenantId
    {
        return new TenantId($this->id);
    }

    public function isOnPaidPlan(): bool
    {
        return $this->plan->isPaid();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'plan'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
