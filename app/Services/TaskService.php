<?php

namespace App\Services;

use App\Http\Requests\TaskRequest;
use App\Repositories\Contracts\TaskInterface;

class TaskService
{
    public function __construct(
        protected TaskInterface $taskRepository
    ) {}

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
        return $this->taskRepository->store($request, $projectId, $userId);
    }

    public function ChangeStatus(int $taskId, int $statusId)
    {
        return $this->taskRepository->ChangeStatus($taskId, $statusId);
    }

    public function delete(int $id)
    {
        return $this->taskRepository->delete($id);
    }
}
