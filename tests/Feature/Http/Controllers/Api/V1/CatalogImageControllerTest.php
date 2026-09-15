<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Images\Enums\ImageCollection;
use App\Domain\Shared\Images\Models\Image;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function store_creates_an_image_for_an_event_and_stores_its_file(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();
        $user = $this->catalogManager();

        $response = $this->withToken($user->createToken('test')->plainTextToken)
            ->post('/api/v1/catalog/events/'.$event->slug.'/images', [
                'file' => $this->imageUpload(),
                'collection' => ImageCollection::Gallery->value,
                'alt_text' => 'Event image',
                'sort_order' => 2,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.collection', ImageCollection::Gallery->value)
            ->assertJsonPath('data.alt_text', 'Event image')
            ->assertJsonPath('data.sort_order', 2);

        $image = $this->responseImage($response->json('data.id'));
        Assert::assertSame($event->id, $image->imageable_id);
        Storage::disk('public')->assertExists($image->path);
    }

    #[Test]
    public function store_creates_an_image_for_a_ticket_type(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $user = $this->catalogManager();

        $response = $this->withToken($user->createToken('test')->plainTextToken)
            ->post('/api/v1/catalog/events/'.$event->slug.'/ticket-types/'.$ticketType->slug.'/images', [
                'file' => $this->imageUpload(),
                'collection' => ImageCollection::Announcement->value,
            ]);

        $response->assertCreated();

        $image = $this->responseImage($response->json('data.id'));
        Assert::assertSame($ticketType->id, $image->imageable_id);
        Storage::disk('public')->assertExists($image->path);
    }

    #[Test]
    public function store_returns_422_when_required_image_attributes_are_missing(): void
    {
        $event = Event::factory()->create();
        $user = $this->catalogManager();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events/'.$event->slug.'/images')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.pointer', '#/file');
    }

    #[Test]
    public function update_replaces_the_file_and_removes_the_previous_file(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();
        $image = Image::factory()->for($event, 'imageable')->create(['path' => 'catalog/old.png']);
        Storage::disk('public')->put($image->path, 'old image');
        $user = $this->catalogManager();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->patch('/api/v1/catalog/events/'.$event->slug.'/images/'.$image->id, [
                'file' => $this->imageUpload(),
                'alt_text' => 'Updated image',
            ])
            ->assertOk()
            ->assertJsonPath('data.alt_text', 'Updated image');

        $image->refresh();
        Storage::disk('public')->assertMissing('catalog/old.png');
        Storage::disk('public')->assertExists($image->path);
    }

    #[Test]
    public function destroy_removes_the_image_record_and_file(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create();
        $image = Image::factory()->for($event, 'imageable')->create(['path' => 'catalog/delete.png']);
        Storage::disk('public')->put($image->path, 'image');
        $user = $this->catalogManager();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/catalog/events/'.$event->slug.'/images/'.$image->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('catalog/delete.png');
    }

    #[Test]
    public function update_returns_404_when_image_does_not_belong_to_the_event(): void
    {
        $event = Event::factory()->create();
        $image = Image::factory()->for(Event::factory(), 'imageable')->create();
        $user = $this->catalogManager();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->patchJson('/api/v1/catalog/events/'.$event->slug.'/images/'.$image->id, [
                'alt_text' => 'Unauthorized update',
            ])
            ->assertNotFound();
    }

    private function catalogManager(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(UserRole::CatalogManager->value);

        return $user;
    }

    private function imageUpload(): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9J1wAAAABJRU5ErkJggg==', true);

        if ($contents === false) {
            throw new LogicException('Invalid test image content.');
        }

        return UploadedFile::fake()->createWithContent(
            'image.png',
            $contents,
        );
    }

    private function responseImage(mixed $imageId): Image
    {
        Assert::assertIsString($imageId);

        return Image::query()->whereKey($imageId)->firstOrFail();
    }
}
