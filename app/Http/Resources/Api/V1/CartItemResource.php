<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Orders\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /** @return array<string, int|string> */
    public function toArray(Request $request): array
    {
        /** @var CartItem $cartItem */
        $cartItem = $this->resource;

        return [
            'id' => $cartItem->id,
            'ticket_type_id' => $cartItem->ticket_type_id,
            'quantity' => $cartItem->quantity,
        ];
    }
}
