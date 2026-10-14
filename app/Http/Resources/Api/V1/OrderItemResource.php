<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /** @return array<string, int|string> */
    public function toArray(Request $request): array
    {
        /** @var OrderItem $orderItem */
        $orderItem = $this->resource;

        return [
            'id' => $orderItem->id,
            'ticket_type_id' => $orderItem->ticket_type_id,
            'ticket_type_name' => $orderItem->ticket_type_name,
            'unit_price_amount' => $orderItem->unit_price_amount,
            'currency' => $orderItem->currency->value,
            'quantity' => $orderItem->quantity,
        ];
    }
}
