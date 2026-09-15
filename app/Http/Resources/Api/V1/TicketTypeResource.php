<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Catalog\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var TicketType $ticketType */
        $ticketType = $this->resource;

        return [
            'id' => $ticketType->id,
            'slug' => $ticketType->slug,
            'name' => $ticketType->name,
            'description' => $ticketType->description,
            'price_amount' => $ticketType->price_amount,
            'currency' => $ticketType->currency->value,
            'capacity' => $ticketType->capacity,
            'sales_limit_per_user' => $ticketType->sales_limit_per_user,
            'sales_starts_at' => $ticketType->sales_starts_at?->toISOString(),
            'sales_ends_at' => $ticketType->sales_ends_at?->toISOString(),
            'status' => $ticketType->status->value,
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
