<?php

namespace App\Repositories;

use App\Http\Requests\StatusRequest;
use App\Models\Status;
use App\Repositories\Contracts\StatusInterface;
use Illuminate\Support\Facades\Auth;

class StatusRepository implements StatusInterface
{
    public function index(int $id)
    {
        return Status::where('user_id', Auth::user()->id)->where('project_id', $id)->with('tasks')->get();
    }

    public function store(StatusRequest $request, int $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->projects()->where('projects.id', $id)->firstOrFail();
        Status::create([
            'name' => $request->name,
            'user_id' => Auth::user()->id,
            'project_id' => $id
        ]);
        return true;
    }

    public function update(StatusRequest $request, int $id)
    {
        $status = Status::where('user_id', Auth::user()->id)->where('project_id', $id)->firstOrFail();
        if($status->name == 'Completed' or $status->name == 'New'){throw new \Exception('This status cannot be updated');}

        $status->update(['name' => $request->name]);
        return true;
    }

    public function delete(Status $status)
    {
        $status->delete();
        return true;
    }
}
