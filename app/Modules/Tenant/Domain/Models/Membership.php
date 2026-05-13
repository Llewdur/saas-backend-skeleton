<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Models;

use App\Models\User;
use App\Modules\Tenant\Database\Factories\MembershipFactory;
use App\Modules\Tenant\Domain\Concerns\BelongsToTenant;
use App\Modules\Tenant\Domain\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property Role $role
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use BelongsToTenant, HasFactory;

    // tenant_id is intentionally NOT fillable — BelongsToTenant's `creating`
    // hook auto-fills it from the resolved TenantContext, and any direct
    // creation path (factories, seeders) must set it via attribute access.
    // Letting it through mass-assign would re-open the cross-tenant forge
    // attack the trait is designed to close.
    protected $fillable = ['user_id', 'role'];

    protected $casts = [
        'role' => Role::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): MembershipFactory
    {
        return MembershipFactory::new();
    }
}
