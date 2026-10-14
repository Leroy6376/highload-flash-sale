<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartItem> */
class CartItemFactory extends Factory
{
    #[\Override]
    protected $model = CartItem::class;

    /** @return array<model-property<CartItem>, mixed> */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'quantity' => fake()->numberBetween(1, 4),
            'ticket_type_id' => TicketType::factory(),
        ];
    }
}
