<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UserResource",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Ahmed Ali"),
        new OA\Property(property: "email", type: "string", format: "email", example: "ahmed@example.com"),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "+966501234567"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-07-20T08:30:00.000000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-07-20T09:15:00.000000Z"),
        new OA\Property(property: "active", type: "boolean", example: true),
        new OA\Property(property: "roles",type: "array",items: new OA\Items(ref: "#/components/schemas/RoleResource"),nullable: true),
        new OA\Property(property: "projects",type: "array",items: new OA\Items(ref: "#/components/schemas/ProjectResource"),nullable: true),
        new OA\Property(property: "statuses",type: "array",items: new OA\Items(ref: "#/components/schemas/StatusResource"),nullable: true),
        new OA\Property(property: "tasks",type: "array",items: new OA\Items(ref: "#/components/schemas/TaskResource"),nullable: true)
    ]
)]

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'active' => $this->is_active,

            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'statuses' => StatusResource::collection($this->whenLoaded('statuses')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks'))
        ];
    }
}
