<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Http\Requests\RolesRequest;
use App\Repositories\Contracts\UserInterface;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\UnauthorizedException;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserInterface $userRepository
    ) {}

    protected function authorized()
    {
        $user = Auth::user();
        if (!$user) { throw new UnauthorizedException('Unauthenticated', 401); }
        $hasAccess = $user->roles->contains(function ($role) {
            return in_array($role->name, ['admin', 'owner']);
        });
        if (!$hasAccess) { throw new UnauthorizedException('Unauthorized', 403); }
    }

    public function index()
    {
        $this->authorized();
        return $this->userRepository->index();
    }

    public function UsersRole(string $roleName)
    {
        $this->authorized();

        if (!RolesEnum::tryFrom($roleName)) {
            throw ValidationException::withMessages(['role' => "role '{$roleName}' is not valid."]);
        }  // 422

        return $this->userRepository->UsersRole($roleName);
    }

    public function UsersProject(int $id)
    {
        $this->authorized();
        return $this->userRepository->UsersProject($id);
    }

    public function ChangeRole(RolesRequest $request, int $id)
    {
        $this->authorized();
        $role = Auth::user()->roles->first(function ($role) {
            return in_array($role->name, ['admin', 'owner']);
        });
        return $this->userRepository->ChangeRole($request, $id, $role->name);
    }

    public function activate(int $id)
    {
        $this->authorized();
        $role = Auth::user()->roles->first(function ($role) {
            return in_array($role->name, ['admin', 'owner']);
        });

        return $this->userRepository->activate($id, $role->name);
    }

    public function ban(int $id)
    {
        $this->authorized();
        $role = Auth::user()->roles->first(function ($role) {
            return in_array($role->name, ['admin', 'owner']);
        });

        return $this->userRepository->ban($id, $role->name);
    }

    public function delete(int $id)
    {
        $this->authorized();
        $user = User::withTrashed()->findOrFail($id);
        if ($user->tasks()->exists() or $user->projects()->exists()) {
            throw new HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => 'Tasks are assigned to this User. You cannot delete him.'
                ], 422)
            );
        }
        $role = Auth::user()->roles->first(function ($role) {
            return in_array($role->name, ['admin', 'owner']);
        });

        return $this->userRepository->delete($user, $role->name);
    }
}
