<?php

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Http\Requests\TaskRequest;
use App\Repositories\Contracts\TaskInterface;
use Illuminate\Support\Facades\Auth;

class TaskService
{
    public function __construct(
        protected TaskInterface $taskRepository
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

    public function index(int $id)
    {
        return $this->taskRepository->index($id);
    }

    public function show(int $id)
    {
        return $this->taskRepository->show($id);
    }

    public function store(TaskRequest $request, int $projectId, int $userId)
    {
        $this->authorized();
        return $this->taskRepository->store($request, $projectId, $userId);
    }

    public function ChangeStatus(int $taskId, int $statusId)
    {
        return $this->taskRepository->ChangeStatus($taskId, $statusId);
    }

    public function delete(int $id)
    {
        $this->authorized();
        return $this->taskRepository->delete($id);
    }
}
