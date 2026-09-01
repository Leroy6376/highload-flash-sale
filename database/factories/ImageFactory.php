<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Models\Event;
use App\Domain\Shared\Images\Enums\ImageCollection;
use App\Domain\Shared\Images\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    #[\Override]
    protected $model = Image::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Image>, mixed>
     */
    public function definition(): array
    {
        return [
            'imageable_type' => new Event()->getMorphClass(),
            'imageable_id' => Event::factory(),
            'collection' => ImageCollection::Gallery,
            'path' => 'catalog/images/'.fake()->uuid().'.png',
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
