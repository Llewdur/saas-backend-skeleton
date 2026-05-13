<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Application\DTOs\ProjectChanges;
use App\Modules\Core\Application\DTOs\ProjectInput;
use App\Modules\Core\Application\UseCases\CreateProject;
use App\Modules\Core\Domain\Models\Project;
use App\Modules\Core\Http\Requests\StoreProjectRequest;
use App\Modules\Core\Http\Requests\UpdateProjectRequest;
use App\Modules\Core\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProjectController
{
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->orderByDesc('id')
            ->cursorPaginate(25);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request, CreateProject $useCase): JsonResponse
    {
        $project = $useCase->execute(ProjectInput::fromArray($request->validated()));

        return ProjectResource::make($project)->response()->setStatusCode(201);
    }

    public function show(Project $project): JsonResponse
    {
        return ProjectResource::make($project)->response();
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $payload = ProjectChanges::fromArray($request->validated())->toEloquentPayload();

        if ($payload !== []) {
            $project->fill($payload)->save();
            $project->refresh();
        }

        return ProjectResource::make($project)->response();
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return new JsonResponse(status: 204);
    }
}
