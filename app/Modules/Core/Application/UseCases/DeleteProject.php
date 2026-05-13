<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Domain\Events\ProjectDeleted;
use App\Modules\Core\Domain\Models\Project;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;

final class DeleteProject
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
    ) {}

    public function execute(Project $project): void
    {
        $this->db->transaction(function () use ($project): void {
            $project->delete();
            $this->events->dispatch(new ProjectDeleted($project));
        });
    }
}
