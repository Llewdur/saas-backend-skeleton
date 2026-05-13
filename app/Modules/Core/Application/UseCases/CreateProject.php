<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Application\DTOs\ProjectInput;
use App\Modules\Core\Domain\Events\ProjectCreated;
use App\Modules\Core\Domain\Models\Project;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;

final class CreateProject
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
    ) {}

    public function execute(ProjectInput $input): Project
    {
        return $this->db->transaction(function () use ($input): Project {
            $project = Project::query()->create([
                'name' => $input->name,
                'description' => $input->description,
            ]);

            // ProjectCreated dispatch happens inside the transaction; the
            // FanOutProjectCreated listener implements ShouldQueueAfterCommit
            // so outbound HTTP / queue work only fires once the commit lands
            // (matches CODING_STANDARDS.md §7 transaction discipline).
            $this->events->dispatch(new ProjectCreated($project));

            return $project;
        });
    }
}
