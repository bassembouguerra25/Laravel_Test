<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Event Resource
 *
 * Transforms Event model to JSON response
 */
class EventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date?->toISOString(),
            'location' => $this->location,
            'created_by' => $this->created_by,
            'organizer' => new UserResource($this->whenLoaded('organizer')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
            'tickets_count' => $this->when($this->relationLoaded('tickets'), fn () => $this->tickets->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
