<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;

class ProjectsController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    #[OA\Get(
        path: '/api/projects',
        summary: 'List of all projects',
        description: 'Fetch all projects with browsing (Paginated) - Requires admin or owner privileges',
        tags: ['Projects'],
        security: [['sanctum' => []]],
        parameters: [ new OA\Parameter(name: 'page',in: 'query',description: 'page number',required: false,schema: new OA\Schema(type: 'integer', default: 1)) ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated Projects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Projects retrieved successfully'),
                        new OA\Property(property: 'data',type: 'array',items: new OA\Items(ref: '#/components/schemas/ProjectResource')),
                        new OA\Property(
                            property: 'links',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', example: 'http://example.com/api/projects?page=1'),
                                new OA\Property(property: 'last', type: 'string', example: 'http://example.com/api/projects?page=5'),
                                new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://example.com/api/projects?page=2')
                            ]
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 5),
                                new OA\Property(property: 'path', type: 'string', example: 'http://example.com/api/projects'),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'to', type: 'integer', example: 10),
                                new OA\Property(property: 'total', type: 'integer', example: 50),
                                new OA\Property(property: 'links',type: 'array',
                                    items: new OA\Items(type: 'object',
                                        properties: [
                                            new OA\Property(property: 'url', type: 'string', nullable: true, example: 'http://example.com/api/projects?page=1'),
                                            new OA\Property(property: 'label', type: 'string', example: '1'),
                                            new OA\Property(property: 'active', type: 'boolean', example: true)
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized - requires admin or owner privileges',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated- Login required'
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function index()
    {
        $projects = $this->projectService->index();

        return ProjectResource::collection($projects)->additional([
            'status' => 'success',
            'message' => 'Projects retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/projects/user',
        summary: 'Projects User',
        description: 'Retrieve Projects User',
        tags: ['Projects'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Projects User retrieved successfully'),
                        new OA\Property(property: 'data',type: 'array',items: new OA\Items(ref: '#/components/schemas/ProjectResource')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated- Login required'
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function ProjectsUser()
    {
        $projects = $this->projectService->ProjectsUser();

        return ProjectResource::collection($projects)->additional([
            'status' => 'success',
            'message' => 'Projects User retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/projects/show/{id}',
        summary: 'get a specific project',
        description: 'Retrieve details of a specific project along with its users and associated tasks.',
        tags: ['Projects'],
        security: [['sanctum' => []]],
        parameters: [ new OA\Parameter(name: 'id',in: 'path',description:'project ID',required: true,schema: new OA\Schema(type: 'integer')) ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Project retrieved successfully'),
                        new OA\Property(property: 'data',type: 'object',ref: '#/components/schemas/ProjectResource')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated- Login required'
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Project not found')
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function show(int $id)
    {
        $project = $this->projectService->show($id);

        return response()->json([
            'data' => new ProjectResource($project),
            'status' => 'success',
            'message' => 'Project retrieved successfully'
        ], 200);
    }

    #[OA\Post(
        path: '/api/projects/create',
        summary: 'Create a new project',
        description: "Create a new project and create statuses('New'status - 'Completed'status) for each user in the project.",
        tags: ['Projects'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'usersIds'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'project title'),
                    new OA\Property(property: 'description', type: 'string', example: 'project description'),
                    new OA\Property(property: 'usersIds',type: 'array',items: new OA\Items(type: 'integer', example: 1),example: [1, 2, 3])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message',type: 'string',example: 'Project created successfully, and statuses have been created for each user in the project.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated- Login required'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - You do not have permission to create a project'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors',type: 'object',additionalProperties: new OA\AdditionalProperties(type: 'array',items: new OA\Items(type: 'string')))
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function store(ProjectRequest $request)
    {
        $this->projectService->store($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully, and statuses have been created for each user in the project.',
        ], 201);
    }

    #[OA\Put(
        path: '/api/projects/update/{id}',
        summary: 'update a project',
        description: "Update a new project and update statuses('New'status - 'Completed'status) for each user in the project.",
        tags: ['Projects'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',in: 'path',description: 'project ID',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'usersIds'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'project title'),
                    new OA\Property(property: 'description', type: 'string', example: 'project description'),
                    new OA\Property(property: 'usersIds',type: 'array',items: new OA\Items(type: 'integer', example: 1),example: [1, 2, 3])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message',type: 'string',example: 'Project updated successfully, if you changed the users, the statuses have been created for each new user in the project and the tasks of removed users have been deleted.')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated- Login required'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - You do not have permission to update this project'
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation Error',
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function update(ProjectRequest $request, int $id)
    {
        $this->projectService->update($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully, if you changed the users, the statuses have been created for each new user in the project and the tasks of removed users have been deleted.',
        ], 200);
    }

    #[OA\Delete(
        path: '/api/projects/delete/{id}',
        summary: 'delete a project',
        description: "Delete a project if there are no tasks assigned to it.",
        tags: ['Projects'],
        security: [['sanctum' => []]],
        parameters: [ new OA\Parameter(name: 'id',in: 'path',description: 'project ID',required: true,schema: new OA\Schema(type: 'integer')) ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Project deleted successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated- Login required'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - You do not have permission to delete this project'
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Project has tasks cannot be deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message',type: 'string',example: 'Tasks are assigned to this project. You cannot delete it.')
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too Many Requests - Throttled"
            )
        ]
    )]
    public function delete(int $id)
    {
        $this->projectService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project deleted successfully',
        ], 200);
    }
}
