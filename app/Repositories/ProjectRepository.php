<?php

namespace App\Repositories;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Models\Status;
use App\Repositories\Contracts\ProjectInterface;
use Illuminate\Support\Facades\Auth;

class ProjectRepository implements ProjectInterface
{
    public function index()
    {
        return Project::paginate(10);
    }

    public function ProjectsUser()
    {
        return Project::whereHas('users', function ($query) { $query->where('user_id', Auth::user()->id);})->get();
    }

    public function show(int $id)
    {
        return Project::with([
            'users' => function ($query) {$query->with(['roles', 'tasks']);}, 'tasks'
        ])->findOrFail($id);
    }

    public function store(ProjectRequest $request)
    {
        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description
        ]);

        $project->users()->attach($request->usersIds);

        $statuses = [];
        foreach ($request->usersIds as $userId) {
            $statuses[] = [
                'name' => 'New',
                'project_id' => $project->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ];
            $statuses[] = [
                'name' => 'Completed',
                'project_id' => $project->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        Status::insert($statuses);
        return true;
    }

    public function update(ProjectRequest $request, int $id)
    {
        $project = Project::findOrFail($id);
        $project->update([
            'title' => $request->title,
            'description' => $request->description
        ]);

        $oldUserIds = $project->users()->pluck('users.id')->toArray();
        $newUserIds = $request->usersIds;

        $removedUserIds = array_diff($oldUserIds, $newUserIds);
        $addedUserIds = array_diff($newUserIds, $oldUserIds);

        if (!empty($removedUserIds)) {
            $project->users()->detach($removedUserIds);
            Status::where('project_id', $project->id)->whereIn('user_id', $removedUserIds)->delete();
        }

        if (!empty($addedUserIds)) {
            $project->users()->attach($addedUserIds);

            $statuses = [];
            foreach ($addedUserIds as $userId) {
                $statuses[] = [
                    'name' => 'New',
                    'project_id' => $project->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $statuses[] = [
                    'name' => 'Completed',
                    'project_id' => $project->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            Status::insert($statuses);
        }
        return true;
    }

    public function delete(Project $project)
    {
        $project->delete();
        return true;
    }
}
