<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Models;

use App\Modules\Core\Database\Factories\ProjectFactory;
use App\Modules\Tenant\Infrastructure\Persistence\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property ?string $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToTenant, HasFactory, LogsActivity;

    protected $fillable = ['name', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
