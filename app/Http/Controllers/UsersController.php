<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolesRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;

class UsersController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}


    public function index()
    {
        $data = $this->userService->index();

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $data
        ]);
    }

    public function UsersRole(string $roleName)
    {
        $data = $this->userService->UsersRole($roleName);

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => UserResource::collection($data)
        ]);
    }

    public function ChangeRole(RolesRequest $request, int $id)
    {
        $this->userService->ChangeRole($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'User role updated successfully',
        ]);
    }

    public function activate(int $id)
    {
        $this->userService->activate($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User Activated successfully',
        ]);
    }

    public function ban(int $id)
    {
        $this->userService->ban($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User Baned successfully',
        ]);
    }

    public function delete(int $id)
    {
        $this->userService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]);
    }
}
