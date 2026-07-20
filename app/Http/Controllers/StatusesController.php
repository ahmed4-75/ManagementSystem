<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;
use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Services\StatusService;

class StatusesController extends Controller
{
    public function __construct(
        protected StatusService $statusService
    ) {}

    #[OA\Get(
        path: '/api/statuses/{id}',
        summary: 'Get all statuses for a project',
        tags: ['Statuses'],
        security: [['sanctum' => []]],
        parameters: [ new OA\Parameter(name: 'id',description: 'Project ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer')) ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statuses retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Statuses retrieved successfully'),
                        new OA\Property(property: 'data',type: 'array',items: new OA\Items(ref: '#/components/schemas/StatusResource'))
                    ]
                )
            ),
            new OA\Response(response: 401,description: 'Unauthorized'),
            new OA\Response(response: 404,description: 'Project not found'),
            new OA\Response(response: 429,description: 'Too Many Requests - Throttled')
        ]
    )]
    public function index(int $id)
    {
        $statuses = $this->statusService->index($id);

        return StatusResource::collection($statuses)->additional([
            'status' => 'success',
            'message' => 'Statuses retrieved successfully'
        ]);
    }

    #[OA\Post(
        path: '/api/statuses/create/{id}',
        summary: 'Create a new status for a project',
        tags: ['Statuses'],
        security: [['sanctum' => []]],
        parameters: [ new OA\Parameter(name: 'id',description: 'Project ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer')) ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',type: 'string',maxLength: 255,example: 'In Progress',description: 'Status name. Cannot be "New" or "Completed"')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Status created successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error - Name is required or invalid'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function store(StatusRequest $request, int $id)
    {
        $this->statusService->store($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Status created successfully'
        ]);
    }

    #[OA\Put(
        path: '/api/statuses/update/{id}',
        summary: 'Update an existing status',
        tags: ['Statuses'],
        security: [['sanctum' => []]],
        parameters: [ new OA\Parameter(name: 'id',description: 'Status ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer')) ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',type: 'string',maxLength: 255,example: 'In Review',description: 'New status name. Cannot be "New" or "Completed"')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Status updated successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Status not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function update(StatusRequest $request, int $id)
    {
        $this->statusService->update($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated successfully'
        ]);
    }

    #[OA\Delete(
        path: '/api/statuses/delete/{id}',
        summary: 'Delete a status',
        tags: ['Statuses'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'Status ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Status deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422,description: 'Cannot delete - Tasks are assigned to this status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Tasks are assigned to this status, You cannot delete it.')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Status not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function delete(int $id)
    {
        $this->statusService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Status deleted successfully'
        ], 200);
    }
}
