<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    #[\Override]
    protected $model = OrderItem::class;

    /** @return array<model-property<OrderItem>, mixed> */
    public function definition(): array
    {
        return [
            'currency' => Currency::Rub,
            'order_id' => Order::factory(),
            'quantity' => fake()->numberBetween(1, 4),
            'ticket_type_id' => TicketType::factory(),
            'ticket_type_name' => fake()->words(2, true),
            'unit_price_amount' => fake()->numberBetween(1_000, 20_000),
        ];
    }
}
