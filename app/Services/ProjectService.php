<?php

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Repositories\Contracts\ProjectInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    public function __construct(
        protected ProjectInterface $projectRepository
    ) {}

    protected function authorized()
    {
        $user = Auth::user();
        $hasAccess = $user->roles->contains(function ($role) {
            return in_array($role->name, ['admin', 'owner']);
        });
        if (!$hasAccess) {
            throw new UnauthorizedException('Unauthorized', 403);
        }
    }

    public function index()
    {
        $this->authorized();
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
        $this->authorized();

        return $this->projectRepository->store($request);
    }

    public function update(ProjectRequest $request, int $id)
    {
        $this->authorized();

        return $this->projectRepository->update($request, $id);
    }

    public function delete(int $id)
    {
        $this->authorized();

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
