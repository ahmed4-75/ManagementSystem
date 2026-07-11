<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\RolesRequest;

interface UserInterface
{
    public function index();
    public function UsersRole(string $roleName);
    public function ChangeRole(RolesRequest $request, int $id, string $roleName);
    public function activate(int $id, string $roleName);
    public function ban(int $id, string $roleName);
    public function delete(int $id, string $roleName);
}
