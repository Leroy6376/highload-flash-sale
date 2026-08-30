<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\ImageCollection;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\EventImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventImage>
 */
class EventImageFactory extends Factory
{
    protected $model = EventImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<EventImage>, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'collection' => ImageCollection::Gallery,
            'path' => 'catalog/events/'.fake()->uuid().'.png',
            'alt_text' => fake()->sentence(3),
            'sort_order' => 0,
        ];
    }

    public function announcement(): static
    {
        return $this->state(fn (array $attributes): array => [
            'collection' => ImageCollection::Announcement,
        ]);
    }
}
