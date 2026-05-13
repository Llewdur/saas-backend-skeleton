<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Events;

use App\Modules\Core\Domain\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ProjectDeleted
{
    use Dispatchable;

    public function __construct(public Project $project) {}
}
