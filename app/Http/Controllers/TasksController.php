<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;

class TasksController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function index(int $id)
    {
        $tasks = $this->taskService->index($id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Tasks Project retrieved successfully',
            'data' => TaskResource::collection($tasks)
        ]);
    }

    public function show(int $id)
    {
        $task = $this->taskService->show($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Task retrieved successfully',
            'data' => new TaskResource($task)
        ]);
    }

    public function store(TaskRequest $request, int $projectId, int $userId)
    {
        $this->taskService->store($request, $projectId, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Task created successfully'
        ], 201);
    }

    public function ChangeStatus(int $taskId, int $statusId)
    {
        $this->taskService->ChangeStatus($taskId, $statusId);

        return response()->json([
            'status' => 'success',
            'message' => 'Task status updated successfully'
        ]);
    }

    public function delete(int $id)
    {
        $this->taskService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully'
        ], 200);
    }
}
