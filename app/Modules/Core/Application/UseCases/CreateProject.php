<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Application\DTOs\ProjectInput;
use App\Modules\Core\Domain\Events\ProjectCreated;
use App\Modules\Core\Domain\Models\Project;
use Illuminate\Contracts\Events\Dispatcher;

final class CreateProject
{
    public function __construct(private readonly Dispatcher $events) {}

    public function execute(ProjectInput $input): Project
    {
        $project = Project::query()->create([
            'name' => $input->name,
            'description' => $input->description,
        ]);

        $this->events->dispatch(new ProjectCreated($project));

        return $project;
    }
}
