<?php

namespace App\Services;

use App\Http\Requests\StatusRequest;
use App\Models\Status;
use App\Repositories\Contracts\StatusInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class StatusService
{
    public function __construct(
        protected StatusInterface $statusRepository
    ) {}

    public function index(int $id)
    {
        return $this->statusRepository->index($id);
    }

    public function store(StatusRequest $request, int $id)
    {
        return $this->statusRepository->store($request, $id);
    }

    public function update(StatusRequest $request, int $id)
    {
        return $this->statusRepository->update($request, $id);
    }

    public function delete(int $id)
    {
        $status = Status::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        if ($status->tasks()->exists()) {
            throw new HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => 'Tasks are assigned to this status, You cannot delete it.'
                ], 422)
            );
        }
        return $this->statusRepository->delete($status);
    }
}
