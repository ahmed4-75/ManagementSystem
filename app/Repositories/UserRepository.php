<?php

namespace App\Repositories;

use App\Http\Requests\RolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserInterface;
use App\Exceptions\UnauthorizedException;

class UserRepository implements UserInterface
{
    protected function checkAdminCannotModify(User $user, string $roleName): void
    {
        $isAdminOrOwner = $user->roles->contains(function ($role) {
            return in_array($role->name, ['owner', 'admin']);
        });
        if ($roleName === 'admin' and $isAdminOrOwner) { throw new UnauthorizedException('Unauthorized',403); }
    }

    public function index()
    {
        return User::with(['projects','roles'])->paginate(15);
    }

    public function UsersRole(string $roleName)
    {
        return User::query()->whereHas('roles', function ($query) use ($roleName) { $query->where('name', $roleName); })->get();
    }

    public function UsersProject(int $id)
    {
        return User::query()->whereHas('projects', function ($query) use ($id) { $query->where('project_id', $id); })->get();
    }

    public function ChangeRole(RolesRequest $request, int $id, string $roleName)
    {
        $user = User::findOrFail($id);
        $this->checkAdminCannotModify($user, $roleName);

        $roleIds = Role::whereIn('name', $request->names)->pluck('id')->toArray();
        $user->roles()->sync($roleIds);
        return true;
    }

    public function activate(int $id, string $roleName)
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->checkAdminCannotModify($user, $roleName);

        $user->restore();
        return true;
    }

    public function ban(int $id, string $roleName)
    {
        $user = User::findOrFail($id);
        $this->checkAdminCannotModify($user, $roleName);

        $user->delete();
        return true;
    }

    public function delete(User $user, string $roleName)
    {
        $this->checkAdminCannotModify($user, $roleName);

        $user->forceDelete();
        return true;
    }
}
