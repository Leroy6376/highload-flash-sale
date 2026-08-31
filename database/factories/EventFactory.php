<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    #[\Override]
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Event>, mixed>
     */
    public function definition(): array
    {
        $now = now('Europe/Moscow');

        return [
            'slug' => Str::slug(fake()->unique()->bothify('event-####-????')),
            'title' => fake()->sentence(4),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'timezone' => 'Europe/Moscow',
            'starts_at' => $now->copy()->addMonth(),
            'ends_at' => $now->copy()->addMonth()->addHours(3),
            'sales_starts_at' => $now->copy()->subWeek(),
            'sales_ends_at' => $now->copy()->addMonth()->subHour(),
            'status' => EventStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Published,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes): array {
            $now = now('Europe/Moscow');

            return [
                'ends_at' => $now->copy()->addMonth()->addHours(3),
                'sales_ends_at' => $now->copy()->addMonth()->subHour(),
                'starts_at' => $now->copy()->addMonth(),
            ];
        });
    }
}
