<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\Images\Models\Image;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function index_returns_published_events_in_chronological_order_with_requested_page_size(): void
    {
        $firstEvent = Event::factory()->published()->create(['starts_at' => '2026-10-01 10:00:00']);
        $secondEvent = Event::factory()->published()->create(['starts_at' => '2026-10-02 10:00:00']);
        Event::factory()->create(['starts_at' => '2026-09-01 10:00:00']);
        Image::factory()->for($firstEvent, 'imageable')->create();

        $this->getJson('/api/v1/catalog/events?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $secondEvent->id)
            ->assertJsonMissing(['id' => $firstEvent->id]);
    }

    #[Test]
    public function index_returns_422_when_per_page_exceeds_the_allowed_limit(): void
    {
        $this->getJson('/api/v1/catalog/events?per_page=101')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.pointer', '#/per_page');
    }

    #[Test]
    public function show_returns_only_active_ticket_types_for_a_published_event(): void
    {
        $event = Event::factory()->published()->create();
        $activeTicketType = TicketType::factory()->active()->for($event)->create();
        TicketType::factory()->for($event)->create();
        Image::factory()->for($event, 'imageable')->create();

        $this->getJson('/api/v1/catalog/events/'.$event->slug)
            ->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonCount(1, 'data.images')
            ->assertJsonCount(1, 'data.ticket_types')
            ->assertJsonPath('data.ticket_types.0.id', $activeTicketType->id);
    }

    #[Test]
    public function show_returns_404_for_an_unpublished_event(): void
    {
        $event = Event::factory()->create();

        $this->getJson('/api/v1/catalog/events/'.$event->slug)->assertNotFound();
    }

    #[Test]
    public function store_returns_401_when_no_token_is_provided(): void
    {
        $this->postJson('/api/v1/catalog/events', $this->eventPayload())->assertUnauthorized();
    }

    #[Test]
    public function store_returns_403_when_user_has_no_catalog_permission(): void
    {
        $user = $this->userWithRole(UserRole::Customer);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events', $this->eventPayload())
            ->assertForbidden();
    }

    #[Test]
    public function store_returns_422_for_an_empty_payload(): void
    {
        $user = $this->userWithRole(UserRole::CatalogManager);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.pointer', '#/slug');
    }

    #[Test]
    public function store_creates_an_event_for_a_catalog_manager(): void
    {
        $user = $this->userWithRole(UserRole::CatalogManager);
        $payload = $this->eventPayload();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.slug', $payload['slug'])
            ->assertJsonPath('data.status', EventStatus::Published->value);

        $this->assertDatabaseHas('events', [
            'slug' => $payload['slug'],
            'title' => $payload['title'],
            'status' => EventStatus::Published->value,
        ]);
    }

    #[Test]
    public function update_persists_changes_for_a_catalog_manager(): void
    {
        $user = $this->userWithRole(UserRole::CatalogManager);
        $event = Event::factory()->create();
        $payload = $this->eventPayload([
            'slug' => 'updated-event',
            'title' => 'Updated event',
            'status' => EventStatus::Cancelled->value,
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->patchJson('/api/v1/catalog/events/'.$event->slug, $payload)
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated event')
            ->assertJsonPath('data.status', EventStatus::Cancelled->value);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'slug' => 'updated-event',
            'status' => EventStatus::Cancelled->value,
        ]);
    }

    #[Test]
    public function destroy_removes_an_event_for_a_catalog_manager(): void
    {
        $user = $this->userWithRole(UserRole::CatalogManager);
        $event = Event::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/catalog/events/'.$event->slug)
            ->assertNoContent();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function eventPayload(array $overrides = []): array
    {
        return array_replace([
            'slug' => 'test-event',
            'title' => 'Test event',
            'timezone' => 'Europe/Moscow',
            'starts_at' => '2026-10-01 10:00:00',
            'status' => EventStatus::Published->value,
        ], $overrides);
    }

    private function userWithRole(UserRole $role): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
