<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "NotificationResource",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "string", example: "550e8400-e29b-41d4-a716-446655440000"),
        new OA\Property(property: "type", type: "string", example: "NewOrderNotification"),
        new OA\Property(property: "title", type: "string", nullable: true, example: "end of task"),
        new OA\Property(property: "message", type: "string", nullable: true, example: "You have an end of task"),
        new OA\Property(property: "read_at", type: "string", format: "date-time", nullable: true, example: "2026-07-20T09:15:30.000000Z"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-07-20T08:30:00.000000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-07-20T08:30:00.000000Z")
    ]
)]

class NotificationResource extends JsonResource
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
            'type' => class_basename($this->type),
            'title' => $this->data['title'] ?? null,
            'message' => $this->data['message'] ?? null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
