<?php

namespace App\Repositories;

use App\Http\Requests\TaskRequest;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Repositories\Contracts\TaskInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskRepository implements TaskInterface
{
    public function index(int $id)
    {
        return Task::where('project_id', $id)->with('user')->get();
    }

    public function show(int $id)
    {
        return Task::with(['project','user'])->findOrFail($id);
    }

    public function store(TaskRequest $request, int $projectId, int $userId)
    {
        $project = Project::findOrFail($projectId);

        if (!$project->users()->where('users.id', $userId)->exists()) {
            throw new ModelNotFoundException( 'User is not assigned to this project.', 404);
        }
        $statusId = Status::where('project_id', $projectId)->where('user_id', $userId)->where('name', 'New')->firstOrFail()->id;
        Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'project_id' => $projectId,
            'user_id' => $userId,
            'status_id' => $statusId
        ]);
        return true;
    }

    public function ChangeStatus(int $taskId, int $statusId)
    {
        $task = Task::findOrFail($taskId);
        Status::findOrFail($statusId);
        $task->update(['status_id' => $statusId]);
        return true;
    }

    public function delete(int $id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return true;
    }
}
