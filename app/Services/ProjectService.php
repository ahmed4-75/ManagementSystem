<?php

namespace App\Services;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Repositories\Contracts\ProjectInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectService
{
    public function __construct(
        protected ProjectInterface $projectRepository
    ) {}

    public function index()
    {
        return $this->projectRepository->index();
    }

    public function ProjectsUser()
    {
        return $this->projectRepository->ProjectsUser();
    }

    public function show(int $id)
    {
        return $this->projectRepository->show($id);
    }

    public function store(ProjectRequest $request)
    {
        return $this->projectRepository->store($request);
    }
    public function update(ProjectRequest $request, int $id)
    {
        return $this->projectRepository->update($request, $id);
    }

    public function delete(int $id)
    {
        $project = Project::findOrFail($id);
        if ($project->tasks()->exists()) {
            throw new HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => 'Tasks are assigned to this project. You cannot delete it.'
                ], 422)
            );
        }
        return $this->projectRepository->delete($project);
    }
}
