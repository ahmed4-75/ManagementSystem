<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;

class TasksController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    #[OA\Get(
        path: '/api/tasks/project/{id}',
        summary: 'Get all tasks for a project',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'Project ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tasks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Tasks Project retrieved successfully'),
                        new OA\Property(property: 'data',type: 'array',items: new OA\Items(ref: '#/components/schemas/TaskResource')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function index(int $id)
    {
        $tasks = $this->taskService->index($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Tasks Project retrieved successfully',
            'data' => TaskResource::collection($tasks)
        ]);
    }

    #[OA\Get(
        path: '/api/tasks/show/{id}',
        summary: 'Get a single task',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'Task ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TaskResource')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - You do not have permission to access this task'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function show(int $id)
    {
        $task = $this->taskService->show($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Task retrieved successfully',
            'data' => new TaskResource($task)
        ]);
    }

    #[OA\Post(
        path: '/api/tasks/create/{ProjectId}/{UserId}',
        summary: 'Create a new task for a user in a project',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'ProjectId',description: 'Project ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'UserId',description: 'User ID to assign the task to',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description'],
                properties: [
                    new OA\Property(property: 'title',type: 'string',maxLength: 255,example: 'Implement authentication'),
                    new OA\Property(property: 'description',type: 'string',example: 'Implement JWT authentication for the API endpoints')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Task created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task created successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin or Owner role required'),
            new OA\Response(response: 404, description: 'Project or User not found / User not assigned to project'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function store(TaskRequest $request, int $projectId, int $userId)
    {
        $this->taskService->store($request, $projectId, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Task created successfully'
        ], 201);
    }

    #[OA\Put(
        path: '/api/tasks/change-status/{TaskId}/{StatusId}',
        summary: 'Change task status',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'TaskId',description: 'Task ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'StatusId',description: 'New Status ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task status updated successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Task or Status not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function ChangeStatus(int $taskId, int $statusId)
    {
        $this->taskService->ChangeStatus($taskId, $statusId);

        return response()->json([
            'status' => 'success',
            'message' => 'Task status updated successfully'
        ]);
    }

    #[OA\Delete(
        path: '/api/tasks/delete/{id}',
        summary: 'Delete a task',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'Task ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Task deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin or Owner role required'),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function delete(int $id)
    {
        $this->taskService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully'
        ], 200);
    }
}
