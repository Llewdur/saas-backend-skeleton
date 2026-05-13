<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Models;

use App\Models\User;
use App\Modules\Tenant\Database\Factories\MembershipFactory;
use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Infrastructure\Persistence\BelongsToTenant;
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

    protected $fillable = ['tenant_id', 'user_id', 'role'];

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
