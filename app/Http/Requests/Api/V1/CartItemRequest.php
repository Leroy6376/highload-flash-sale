<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Orders\Data\CartItemData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CartItemRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'ticket_type_id' => ['required', 'uuid', Rule::exists(TicketType::class, 'id')],
        ];
    }

    public function cartItemData(): CartItemData
    {
        /**
         * @var array{
         *     ticket_type_id: string,
         *     quantity: int
         * } $validated
         */
        $validated = $this->validated();

        return new CartItemData(
            ticketTypeId: $validated['ticket_type_id'],
            quantity: $validated['quantity'],
        );
    }
}
