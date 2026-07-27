<?php

namespace App\Repositories;

use App\Events\EndTask;
use App\Http\Requests\TaskRequest;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\TaskInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class TaskRepository implements TaskInterface
{
    public function index(int $id)
    {
        return Task::where('project_id', $id)->with('user')->get();
    }

    public function show(int $id)
    {
        return Task::query()->where('user_id', Auth::id())->with(['project','user'])->findOrFail($id);
    }

    public function store(TaskRequest $request, int $projectId, int $userId)
    {
        $project = Project::findOrFail($projectId);

        if (!$project->users()->where('users.id', $userId)->exists()) {
            throw new ModelNotFoundException( 'User is not assigned to this project.', 404);
        }
        $status = Status::where('project_id', $projectId)->where('user_id', $userId)->where('name', 'New')->firstOrFail();
        $statusId = $status->id;
        Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'project_id' => $projectId,
            'user_id' => $userId,
            'status_id' => $statusId
        ]);
        $targetUser = User::findOrFail($userId);
        $message = 'A New Task , ' . $targetUser->name.' '. $targetUser->id;
        try{
            broadcast(new EndTask($message, $targetUser->id));
            Log::info('✅ Broadcasted to user: ' . $targetUser->id);
        }catch(\Exception $e){
            Log::error('❌ Broadcasting failed: ' . $e->getMessage());
        }
        return true;
    }

    public function ChangeStatus(int $taskId, int $statusId)
    {
        $task = Task::findOrFail($taskId);
        $status = Status::where('id', $statusId)->where('user_id', Auth::user()->id)->where('project_id', $task->project_id)->firstOrFail();

        if ($status->name === 'Completed') {
            $projectId = $status->project_id;
            $project = Project::with('users')->findOrFail($projectId);
            $targetUsers = $project->users;
            foreach ($targetUsers as $user) {
                $message = 'A Task has ended, ' . $user->name.' '. $user->id;
                try {
                    broadcast(new EndTask($message, $user->id));
                    Log::info('✅ Broadcasted to user: ' . $user->id);
                } catch (\Exception $e) {
                    Log::error('❌ Broadcasting failed: ' . $e->getMessage());
                }
            }
        }
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
