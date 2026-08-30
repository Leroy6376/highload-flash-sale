<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\EventImage;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Catalog\Models\TicketTypeImage;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_event_exposes_ticket_types_images_and_expected_casts(): void
    {
        $event = Event::factory()->published()->upcoming()->create();
        $ticketType = TicketType::factory()->for($event)->active()->create();
        $announcement = EventImage::factory()->for($event)->announcement()->create();
        $firstGalleryImage = EventImage::factory()->for($event)->create(['sort_order' => 1]);
        $secondGalleryImage = EventImage::factory()->for($event)->create(['sort_order' => 2]);

        $event->refresh()->load('ticketTypes', 'announcementImage', 'galleryImages');

        self::assertTrue(Str::isUuid($event->id));
        self::assertSame(EventStatus::Published, $event->status);
        self::assertInstanceOf(CarbonImmutable::class, $event->starts_at);
        self::assertTrue($event->ticketTypes->contains($ticketType));
        self::assertNotNull($event->announcementImage);
        self::assertTrue($event->announcementImage->is($announcement));
        self::assertSame([$firstGalleryImage->id, $secondGalleryImage->id], $event->galleryImages->modelKeys());
        self::assertSame(TicketTypeStatus::Active, $ticketType->refresh()->status);
        self::assertSame(Currency::Rub, $ticketType->currency);
    }

    public function test_ticket_type_slug_is_unique_within_an_event(): void
    {
        $event = Event::factory()->create();
        TicketType::factory()->for($event)->create(['slug' => 'standard']);

        $this->expectException(QueryException::class);

        TicketType::factory()->for($event)->create(['slug' => 'standard']);
    }

    public function test_deleting_an_event_removes_ticket_types_and_images(): void
    {
        $event = Event::factory()->create();
        $eventImage = EventImage::factory()->for($event)->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $ticketTypeImage = TicketTypeImage::factory()->for($ticketType)->create();

        $event->delete();

        $this->assertModelMissing($eventImage);
        $this->assertModelMissing($ticketType);
        $this->assertModelMissing($ticketTypeImage);
    }

    public function test_catalog_seeder_creates_idempotent_catalog_and_public_images(): void
    {
        Storage::fake('public');

        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        self::assertSame(3, Event::count());
        self::assertSame(6, TicketType::count());
        self::assertSame(9, EventImage::count());
        self::assertSame(18, TicketTypeImage::count());
        Storage::disk('public')->assertExists('catalog/events/night-waves-2026/announcement.png');
        Storage::disk('public')->assertExists('catalog/ticket-types/night-waves-2026/standard/gallery-2.png');
    }
}
