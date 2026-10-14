<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    #[\Override]
    protected $model = Order::class;

    /** @return array<model-property<Order>, mixed> */
    public function definition(): array
    {
        return [
            'currency' => Currency::Rub,
            'expires_at' => now()->addMinutes(15),
            'status' => OrderStatus::PendingPayment,
            'total_amount' => fake()->numberBetween(1_000, 50_000),
            'user_id' => User::factory(),
        ];
    }
}
