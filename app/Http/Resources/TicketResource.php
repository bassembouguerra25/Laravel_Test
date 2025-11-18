<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ticket Resource
 *
 * Transforms Ticket model to JSON response
 */
class TicketResource extends JsonResource
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
            'type' => $this->type,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'available_quantity' => $this->available_quantity,
            'event_id' => $this->event_id,
            'event' => new EventResource($this->whenLoaded('event')),
            'bookings_count' => $this->when($this->relationLoaded('bookings'), fn () => $this->bookings->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
