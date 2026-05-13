<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Application\DTOs\ProjectChanges;
use App\Modules\Core\Domain\Events\ProjectUpdated;
use App\Modules\Core\Domain\Models\Project;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;

/**
 * Applies a sparse update to a Project.
 *
 * Has reason to exist beyond a one-line proxy: wraps the write in a
 * transaction and emits ProjectUpdated so the audit log entry and any
 * webhook fan-out fire only after the commit (ShouldQueueAfterCommit on
 * listeners).
 */
final class UpdateProject
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
    ) {}

    public function execute(Project $project, ProjectChanges $changes): Project
    {
        $payload = $changes->toEloquentPayload();
        if ($payload === []) {
            return $project;
        }

        return $this->db->transaction(function () use ($project, $payload): Project {
            $project->fill($payload)->save();
            $this->events->dispatch(new ProjectUpdated($project));

            return $project->refresh();
        });
    }
}
