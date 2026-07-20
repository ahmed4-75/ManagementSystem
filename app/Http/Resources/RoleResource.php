<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "RoleResource",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "admin"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-07-20T08:30:00.000000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-07-20T09:15:00.000000Z"),
        new OA\Property(property: "users",type: "array",items: new OA\Items(ref: "#/components/schemas/UserResource"),nullable: true)
    ]
)]

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
