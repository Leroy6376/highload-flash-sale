<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Enums\ImageCollection;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\EventImage;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Catalog\Models\TicketTypeImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * @phpstan-type TicketTypeSeed array{
 *     slug: string,
 *     name: string,
 *     description: string,
 *     price_amount: int,
 *     currency: Currency,
 *     capacity: int,
 *     sales_limit_per_user: int,
 *     sales_starts_at: string,
 *     sales_ends_at: string,
 *     status: TicketTypeStatus
 * }
 * @phpstan-type EventSeed array{
 *     slug: string,
 *     title: string,
 *     short_description: string,
 *     description: string,
 *     starts_at: string,
 *     ends_at: string,
 *     sales_starts_at: string,
 *     sales_ends_at: string,
 *     status: EventStatus,
 *     ticket_types: list<TicketTypeSeed>
 * }
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->events() as $eventData) {
            $ticketTypes = $eventData['ticket_types'];
            unset($eventData['ticket_types']);

            $event = Event::query()->updateOrCreate(
                ['slug' => $eventData['slug']],
                $eventData,
            );

            $this->seedEventImages($event);

            foreach ($ticketTypes as $ticketTypeData) {
                $ticketType = $event->ticketTypes()->updateOrCreate(
                    ['slug' => $ticketTypeData['slug']],
                    $ticketTypeData,
                );

                $this->seedTicketTypeImages($ticketType, $event->slug);
            }
        }
    }

    /**
     * @return list<EventSeed>
     */
    private function events(): array
    {
        return [
            [
                'slug' => 'night-waves-2026',
                'title' => 'Night Waves 2026',
                'short_description' => 'Ночной фестиваль электронной музыки под открытым небом.',
                'description' => 'Night Waves объединяет лайв-сеты, визуальное искусство и электронную музыку на одной сцене.',
                'starts_at' => '2026-10-17 19:00:00+03:00',
                'ends_at' => '2026-10-18 03:00:00+03:00',
                'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                'sales_ends_at' => '2026-10-17 22:00:00+03:00',
                'status' => EventStatus::Published,
                'ticket_types' => [
                    [
                        'slug' => 'standard',
                        'name' => 'Стандарт',
                        'description' => 'Доступ на территорию фестиваля с 19:00.',
                        'price_amount' => 390_000,
                        'currency' => Currency::Rub,
                        'capacity' => 1_000,
                        'sales_limit_per_user' => 4,
                        'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                        'sales_ends_at' => '2026-10-17 22:00:00+03:00',
                        'status' => TicketTypeStatus::Active,
                    ],
                    [
                        'slug' => 'premium',
                        'name' => 'Премиум',
                        'description' => 'Ранний вход и отдельная зона отдыха.',
                        'price_amount' => 790_000,
                        'currency' => Currency::Rub,
                        'capacity' => 200,
                        'sales_limit_per_user' => 2,
                        'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                        'sales_ends_at' => '2026-10-17 22:00:00+03:00',
                        'status' => TicketTypeStatus::Active,
                    ],
                ],
            ],
            [
                'slug' => 'house-of-silence',
                'title' => 'Дом тишины',
                'short_description' => 'Камерная современная постановка о памяти и выборе.',
                'description' => 'Новая театральная работа, в которой свет, музыка и пауза становятся равноправными героями истории.',
                'starts_at' => '2026-11-21 19:30:00+03:00',
                'ends_at' => '2026-11-21 21:15:00+03:00',
                'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                'sales_ends_at' => '2026-11-21 19:30:00+03:00',
                'status' => EventStatus::Published,
                'ticket_types' => [
                    [
                        'slug' => 'base',
                        'name' => 'Базовый',
                        'description' => 'Вход на спектакль.',
                        'price_amount' => 250_000,
                        'currency' => Currency::Rub,
                        'capacity' => 350,
                        'sales_limit_per_user' => 6,
                        'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                        'sales_ends_at' => '2026-11-21 19:30:00+03:00',
                        'status' => TicketTypeStatus::Active,
                    ],
                    [
                        'slug' => 'supporter',
                        'name' => 'Друг театра',
                        'description' => 'Вход на спектакль и вклад в будущие постановки.',
                        'price_amount' => 500_000,
                        'currency' => Currency::Rub,
                        'capacity' => 50,
                        'sales_limit_per_user' => 2,
                        'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                        'sales_ends_at' => '2026-11-21 19:30:00+03:00',
                        'status' => TicketTypeStatus::Active,
                    ],
                ],
            ],
            [
                'slug' => 'universe-within-us',
                'title' => 'Вселенная внутри нас',
                'short_description' => 'Лекция в планетарии о масштабе космоса и человеческом любопытстве.',
                'description' => 'Популярный астрофизик проведёт зрителей от ближайших планет к далёким галактикам и ответит на вопросы после лекции.',
                'starts_at' => '2026-12-05 18:00:00+03:00',
                'ends_at' => '2026-12-05 20:00:00+03:00',
                'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                'sales_ends_at' => '2026-12-05 18:00:00+03:00',
                'status' => EventStatus::Published,
                'ticket_types' => [
                    [
                        'slug' => 'lecture',
                        'name' => 'Лекция',
                        'description' => 'Доступ к лекции и сессии вопросов.',
                        'price_amount' => 180_000,
                        'currency' => Currency::Rub,
                        'capacity' => 500,
                        'sales_limit_per_user' => 6,
                        'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                        'sales_ends_at' => '2026-12-05 18:00:00+03:00',
                        'status' => TicketTypeStatus::Active,
                    ],
                    [
                        'slug' => 'lecture-plus',
                        'name' => 'Лекция плюс',
                        'description' => 'Лекция, приоритетная регистрация на вопросы и печатный конспект.',
                        'price_amount' => 320_000,
                        'currency' => Currency::Rub,
                        'capacity' => 100,
                        'sales_limit_per_user' => 2,
                        'sales_starts_at' => '2026-08-30 10:00:00+03:00',
                        'sales_ends_at' => '2026-12-05 18:00:00+03:00',
                        'status' => TicketTypeStatus::Active,
                    ],
                ],
            ],
        ];
    }

    private function seedEventImages(Event $event): void
    {
        foreach ($this->imageDefinitions($event->slug) as $image) {
            $destination = "catalog/events/{$event->slug}/{$image['filename']}";
            $this->copyImage($image['source'], $destination);

            EventImage::query()->updateOrCreate(
                $this->eventImageIdentity($event, $image['collection'], $destination),
                [
                    'path' => $destination,
                    'alt_text' => $image['alt_text'],
                    'sort_order' => $image['sort_order'],
                ],
            );
        }
    }

    private function seedTicketTypeImages(TicketType $ticketType, string $eventSlug): void
    {
        foreach ($this->imageDefinitions($eventSlug) as $image) {
            $destination = "catalog/ticket-types/{$eventSlug}/{$ticketType->slug}/{$image['filename']}";
            $this->copyImage($image['source'], $destination);

            TicketTypeImage::query()->updateOrCreate(
                $this->ticketTypeImageIdentity($ticketType, $image['collection'], $destination),
                [
                    'path' => $destination,
                    'alt_text' => $image['alt_text'],
                    'sort_order' => $image['sort_order'],
                ],
            );
        }
    }

    /**
     * @return array{event_id: string, collection: string, path?: string}
     */
    private function eventImageIdentity(Event $event, ImageCollection $collection, string $path): array
    {
        $identity = [
            'event_id' => $event->id,
            'collection' => $collection->value,
        ];

        if ($collection === ImageCollection::Gallery) {
            $identity['path'] = $path;
        }

        return $identity;
    }

    /**
     * @return array{ticket_type_id: string, collection: string, path?: string}
     */
    private function ticketTypeImageIdentity(TicketType $ticketType, ImageCollection $collection, string $path): array
    {
        $identity = [
            'ticket_type_id' => $ticketType->id,
            'collection' => $collection->value,
        ];

        if ($collection === ImageCollection::Gallery) {
            $identity['path'] = $path;
        }

        return $identity;
    }

    /**
     * @return array<int, array{collection: ImageCollection, filename: string, source: string, alt_text: string, sort_order: int}>
     */
    private function imageDefinitions(string $eventSlug): array
    {
        $sourceDirectory = database_path("seeders/assets/catalog/{$this->assetGroup($eventSlug)}");

        return [
            [
                'collection' => ImageCollection::Announcement,
                'filename' => 'announcement.png',
                'source' => "{$sourceDirectory}/announcement.png",
                'alt_text' => 'Анонс события',
                'sort_order' => 0,
            ],
            [
                'collection' => ImageCollection::Gallery,
                'filename' => 'gallery-1.png',
                'source' => "{$sourceDirectory}/gallery-1.png",
                'alt_text' => 'Галерея события, изображение 1',
                'sort_order' => 1,
            ],
            [
                'collection' => ImageCollection::Gallery,
                'filename' => 'gallery-2.png',
                'source' => "{$sourceDirectory}/gallery-2.png",
                'alt_text' => 'Галерея события, изображение 2',
                'sort_order' => 2,
            ],
        ];
    }

    private function assetGroup(string $eventSlug): string
    {
        return match ($eventSlug) {
            'night-waves-2026' => 'music',
            'house-of-silence' => 'theatre',
            'universe-within-us' => 'science',
            default => throw new \InvalidArgumentException("Unknown event slug [{$eventSlug}]."),
        };
    }

    private function copyImage(string $source, string $destination): void
    {
        $contents = file_get_contents($source);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read seed image [{$source}].");
        }

        Storage::disk('public')->put($destination, $contents);
    }
}
