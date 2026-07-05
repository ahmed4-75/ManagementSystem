<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'deleted_at' => $this->deleted_at,

            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'statuses' => StatusResource::collection($this->whenLoaded('statuses')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks'))
            // 'notifications' => NotificationResource::collection($this->whenLoaded('notifications'))
        ];
    }
}
