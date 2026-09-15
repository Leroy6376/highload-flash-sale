<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Catalog\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Event $event */
        $event = $this->resource;

        return [
            'id' => $event->id,
            'slug' => $event->slug,
            'title' => $event->title,
            'short_description' => $event->short_description,
            'description' => $event->description,
            'timezone' => $event->timezone,
            'starts_at' => $event->starts_at?->toISOString(),
            'ends_at' => $event->ends_at?->toISOString(),
            'sales_starts_at' => $event->sales_starts_at?->toISOString(),
            'sales_ends_at' => $event->sales_ends_at?->toISOString(),
            'status' => $event->status->value,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'ticket_types' => TicketTypeResource::collection($this->whenLoaded('ticketTypes')),
        ];
    }
}
