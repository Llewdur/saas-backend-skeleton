<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Application\DTOs\ProjectChanges;
use App\Modules\Core\Domain\Models\Project;

final class UpdateProject
{
    public function execute(Project $project, ProjectChanges $changes): Project
    {
        $payload = $changes->toEloquentPayload();
        if ($payload === []) {
            return $project;
        }

        $project->fill($payload)->save();

        return $project->refresh();
    }
}
