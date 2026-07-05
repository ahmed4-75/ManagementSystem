<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;

class ProjectsController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    public function index()
    {
        $projects = $this->projectService->index();

        return ProjectResource::collection($projects)->additional([
            'status' => 'success',
            'message' => 'Projects retrieved successfully',
        ]);
    }

    public function ProjectsUser()
    {
        $projects = $this->projectService->ProjectsUser();

        return ProjectResource::collection($projects)->additional([
            'status' => 'success',
            'message' => 'Projects User retrieved successfully',
        ]);
    }

    public function show(int $id)
    {
        $project = $this->projectService->show($id);

        return response()->json([
            'data' => new ProjectResource($project),
            'status' => 'success',
            'message' => 'Project retrieved successfully'
        ], 200);
    }

    public function store(ProjectRequest $request)
    {
        $this->projectService->store($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully, and statuses have been created for each user in the project.',
        ], 201);
    }

    public function update(ProjectRequest $request, int $id)
    {
        $this->projectService->update($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
        ], 200);
    }

    public function delete(int $id)
    {
        $this->projectService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project deleted successfully',
        ], 200);
    }
}
