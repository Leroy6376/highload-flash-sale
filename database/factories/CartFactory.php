<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cart> */
class CartFactory extends Factory
{
    #[\Override]
    protected $model = Cart::class;

    /** @return array<model-property<Cart>, mixed> */
    public function definition(): array
    {
        return [
            'expires_at' => now()->addMinutes(15),
            'status' => CartStatus::Active,
            'user_id' => User::factory(),
        ];
    }
}
