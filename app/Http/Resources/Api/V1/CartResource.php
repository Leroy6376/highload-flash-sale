<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Orders\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Cart $cart */
        $cart = $this->resource;

        return [
            'id' => $cart->id,
            'status' => $cart->status->value,
            'expires_at' => $cart->expires_at?->toISOString(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
