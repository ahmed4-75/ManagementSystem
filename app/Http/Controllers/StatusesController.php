<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Services\StatusService;

class StatusesController extends Controller
{
    public function __construct(
        protected StatusService $statusService
    ) {}

    public function index(int $id)
    {
        $statuses = $this->statusService->index($id);

        return StatusResource::collection($statuses)->additional([
            'status' => 'success',
            'message' => 'Statuses retrieved successfully'
        ]);
    }

    public function store(StatusRequest $request, int $id)
    {
        $this->statusService->store($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Status created successfully'
        ]);
    }

    public function update(StatusRequest $request, int $id)
    {
        $this->statusService->update($request, $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated successfully'
        ]);
    }

    public function delete(int $id)
    {
        $this->statusService->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Status deleted successfully'
        ], 200);
    }
}
