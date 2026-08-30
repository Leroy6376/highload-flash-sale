<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<TicketType>, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'slug' => Str::slug(fake()->unique()->bothify('ticket-type-####-????')),
            'name' => fake()->randomElement(['Standard', 'Premium', 'Early bird']),
            'description' => fake()->paragraph(),
            'price_amount' => fake()->numberBetween(1_000, 20_000),
            'currency' => Currency::Rub,
            'capacity' => fake()->numberBetween(50, 1_000),
            'sales_limit_per_user' => fake()->numberBetween(1, 6),
            'sales_starts_at' => now()->subWeek(),
            'sales_ends_at' => now()->addMonth()->subHour(),
            'status' => TicketTypeStatus::Draft,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketTypeStatus::Active,
        ]);
    }
}
