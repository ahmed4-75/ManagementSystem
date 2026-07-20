<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;
use App\Http\Requests\RolesRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;

class UsersController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    #[OA\Get(
        path: '/api/users',
        summary: 'Get all users with pagination',
        description: 'Returns paginated list of all users with their projects and roles. Requires admin or owner role.',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [ new OA\Parameter(name: 'page',in: 'query',description: 'page number',required: false,schema: new OA\Schema(type: 'integer', default: 1)) ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Users retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserResource')),
                                new OA\Property(
                                    property: 'links',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'first', type: 'string', example: 'http://example.com/api/users?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://example.com/api/users?page=5'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://example.com/api/users?page=2')
                                    ]
                                ),
                                new OA\Property(
                                    property: 'meta',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'from', type: 'integer', example: 1),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                                        new OA\Property(property: 'path', type: 'string', example: 'http://example.com/api/users'),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                        new OA\Property(property: 'to', type: 'integer', example: 10),
                                        new OA\Property(property: 'total', type: 'integer', example: 50),
                                        new OA\Property(property: 'links',type: 'array',
                                            items: new OA\Items(type: 'object',
                                                properties: [
                                                    new OA\Property(property: 'url', type: 'string', nullable: true, example: 'http://example.com/api/users?page=1'),
                                                    new OA\Property(property: 'label', type: 'string', example: '1'),
                                                    new OA\Property(property: 'active', type: 'boolean', example: true)
                                                ]
                                            )
                                        )
                                    ]
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin or Owner role required'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function index()
    {
        $data = $this->userService->index();

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $data
        ]);
    }

    #[OA\Get(
        path: '/api/users/role/{roleName}',
        summary: 'Get users by role',
        description: 'Returns all users who have the specified role. Requires admin or owner role.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roleName',description: 'Role name to filter by',in: 'path',required: true,schema: new OA\Schema(ref: '#/components/schemas/RolesEnum'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Users retrieved successfully'),
                        new OA\Property(property: 'data',type: 'array',items: new OA\Items(ref: '#/components/schemas/UserResource'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin or Owner role required'),
            new OA\Response(response: 422, description: 'Invalid role name'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function UsersRole(string $roleName)
    {
        $data = $this->userService->UsersRole($roleName);

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($data)
        ]);
    }

    #[OA\Get(
        path: '/api/users/project/{id}',
        summary: 'Get users assigned to a project',
        description: 'Returns all users assigned to the specified project. Requires admin or owner role.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'Project ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Users retrieved successfully'),
                        new OA\Property(property: 'data',type: 'array',items: new OA\Items(ref: '#/components/schemas/UserResource'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin or Owner role required'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function UsersProject(int $id)
    {
        $data = $this->userService->UsersProject($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($data)
        ]);
    }
    #[OA\Post(
        path: '/api/users/{id}',
        summary: 'Change user roles',
        description: 'Assign new roles to a user. Admin cannot modify other admins or owners.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'User ID to update',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['names'],
                properties: [
                    new OA\Property(property: 'names',type: 'array',description: 'Array of role names',items: new OA\Items(ref: '#/components/schemas/RolesEnum'),example: ['backend', 'frontend'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User role updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'User role updated successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin cannot modify other admins/owners'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error - Invalid role names'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function ChangeRole(RolesRequest $request, int $id)
    {
        $this->userService->ChangeRole($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'User role updated successfully',
        ]);
    }

    #[OA\Put(
        path: '/api/users/activate/{id}',
        summary: 'Activate (restore) a banned user',
        description: 'Restore a soft-deleted (banned) user. Admin cannot activate other admins or owners.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'User ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User activated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'User Activated successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin cannot activate other admins/owners'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function activate(int $id)
    {
        $this->userService->activate($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User Activated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/api/users/ban/{id}',
        summary: 'Ban (soft delete) a user',
        description: 'Soft delete a user to ban them, Admin cannot ban other admins or owners.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'User ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User banned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'User Baned successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin cannot ban other admins/owners'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 429, description: 'Too Many Requests - Throttled')
        ]
    )]
    public function ban(int $id)
    {
        $this->userService->ban($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User Baned successfully',
        ]);
    }

    #[OA\Delete(
        path: '/api/users/destroy/{id}',
        summary: 'Permanently delete a user',
        description: 'Force delete a user permanently, Fails if user has tasks or projects, Admin cannot delete other admins or owners.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id',description: 'User ID',in: 'path',required: true,schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - Admin cannot delete other admins/owners'),
            new OA\Response(response: 422,description: 'Cannot delete - User has assigned tasks or projects',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Tasks are assigned to this User. You cannot delete him.')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 429, description: 'Too Many Requests')
        ]
    )]
    public function delete(int $id)
    {
        $this->userService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]);
    }
}
