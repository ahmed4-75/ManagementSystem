<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "TaskResource",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "title task"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "task description"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-07-20T08:30:00.000000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-07-20T09:15:00.000000Z"),
        new OA\Property(property: "user",ref: "#/components/schemas/UserResource",type: "object",nullable: true),
        new OA\Property(property: "project",ref: "#/components/schemas/ProjectResource",type: "object",nullable: true)
    ]
)]

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return
        [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'project' => $this->whenLoaded('project', fn() => new ProjectResource($this->project))
        ];
    }
}
