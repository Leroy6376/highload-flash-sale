<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Images\Enums\ImageCollection;
use App\Domain\Shared\Images\Models\Image;
use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\EventResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\TicketTypeResource\Pages\CreateTicketType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::CatalogManager->value);
        $this->actingAs($user);
    }

    #[Test]
    public function event_create_and_edit_pages_persist_through_the_shared_use_cases(): void
    {
        /** @phpstan-ignore staticMethod.dynamicCall (Filament registers Livewire testing macros dynamically.) */
        Livewire::test(CreateEvent::class)
            ->set('data', [
                'slug' => 'filament-event',
                'title' => 'Filament event',
                'timezone' => 'Europe/Moscow',
                'starts_at' => '2026-10-01 10:00:00',
                'status' => EventStatus::Published->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $event = Event::query()->where('slug', 'filament-event')->firstOrFail();

        /** @phpstan-ignore staticMethod.dynamicCall (Filament registers Livewire testing macros dynamically.) */
        Livewire::test(EditEvent::class, ['record' => $event->id])
            ->set('data.title', 'Updated Filament event')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Filament event',
        ]);
    }

    #[Test]
    public function ticket_type_create_page_persists_through_the_shared_use_case(): void
    {
        $event = Event::factory()->create();

        /** @phpstan-ignore staticMethod.dynamicCall (Filament registers Livewire testing macros dynamically.) */
        Livewire::test(CreateTicketType::class)
            ->set('data', [
                'event_id' => $event->id,
                'slug' => 'filament-ticket',
                'name' => 'Filament ticket',
                'price_amount' => 2_500,
                'currency' => Currency::Rub->value,
                'capacity' => 100,
                'status' => TicketTypeStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(TicketType::class, [
            'event_id' => $event->id,
            'slug' => 'filament-ticket',
            'price_amount' => 2_500,
        ]);
    }

    #[Test]
    public function event_image_relation_manager_stores_an_image_through_the_shared_use_case(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();
        $imageContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        assert(is_string($imageContents));

        $component = Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => EditEvent::class,
        ]);
        /** @phpstan-ignore staticMethod.dynamicCall (Filament registers Livewire testing macros dynamically.) */
        $component->mountAction(TestAction::make('create')->table());
        $component
            ->set('mountedActions.0.data.file', UploadedFile::fake()->createWithContent('event.png', $imageContents))
            ->set('mountedActions.0.data.collection', ImageCollection::Gallery->value)
            ->set('mountedActions.0.data.alt_text', 'Event image')
            ->set('mountedActions.0.data.sort_order', 10);
        /** @phpstan-ignore staticMethod.dynamicCall (Filament registers Livewire testing macros dynamically.) */
        $component->callMountedAction();
        /** @phpstan-ignore staticMethod.dynamicCall (Filament registers Livewire testing macros dynamically.) */
        $component->assertHasNoActionErrors();

        $image = Image::query()->whereMorphedTo('imageable', $event)->firstOrFail();

        self::assertSame(ImageCollection::Gallery, $image->collection);
        self::assertSame('Event image', $image->alt_text);
        Storage::disk('public')->assertExists($image->path);
    }
}
