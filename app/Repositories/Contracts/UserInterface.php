<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\RolesRequest;
use App\Models\User;

interface UserInterface
{
    public function index();
    public function UsersRole(string $roleName);
    public function UsersProject(int $id);
    public function ChangeRole(RolesRequest $request, int $id, string $roleName);
    public function activate(int $id, string $roleName);
    public function ban(int $id, string $roleName);
    public function delete(User $user, string $roleName);
}
