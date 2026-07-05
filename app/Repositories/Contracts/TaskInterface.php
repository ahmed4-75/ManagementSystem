<?php

namespace App\Repositories\Contracts;

use App\Http\Requests\TaskRequest;

interface TaskInterface
{
    public function index(int $id);
    public function show(int $id);
    public function store(TaskRequest $request, int $projectId, int $userId);
    public function ChangeStatus(int $taskId, int $statusId);
    public function delete(int $id);
}
