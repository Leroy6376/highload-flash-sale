<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Catalog\Enums\ImageCollection;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Catalog\Models\TicketTypeImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketTypeImage>
 */
class TicketTypeImageFactory extends Factory
{
    protected $model = TicketTypeImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<TicketTypeImage>, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_type_id' => TicketType::factory(),
            'collection' => ImageCollection::Gallery,
            'path' => 'catalog/ticket-types/'.fake()->uuid().'.png',
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
