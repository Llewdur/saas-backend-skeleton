<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Application\DTOs\ProjectChanges;
use App\Modules\Core\Application\DTOs\ProjectInput;
use App\Modules\Core\Application\UseCases\CreateProject;
use App\Modules\Core\Application\UseCases\DeleteProject;
use App\Modules\Core\Application\UseCases\ListProjects;
use App\Modules\Core\Application\UseCases\UpdateProject;
use App\Modules\Core\Domain\Models\Project;
use App\Modules\Core\Http\Requests\StoreProjectRequest;
use App\Modules\Core\Http\Requests\UpdateProjectRequest;
use App\Modules\Core\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProjectController
{
    public function index(ListProjects $useCase): AnonymousResourceCollection
    {
        return ProjectResource::collection($useCase->execute());
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

    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $useCase): JsonResponse
    {
        $updated = $useCase->execute($project, ProjectChanges::fromArray($request->validated()));

        return ProjectResource::make($updated)->response();
    }

    public function destroy(Project $project, DeleteProject $useCase): JsonResponse
    {
        $useCase->execute($project);

        return new JsonResponse(status: 204);
    }
}
