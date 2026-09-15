<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketTypeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function index_returns_only_active_ticket_types_for_a_published_event(): void
    {
        $event = Event::factory()->published()->create();
        $activeTicketType = TicketType::factory()->active()->for($event)->create();
        TicketType::factory()->for($event)->create();

        $this->getJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeTicketType->id);
    }

    #[Test]
    public function index_returns_404_for_an_unpublished_event(): void
    {
        $event = Event::factory()->create();

        $this->getJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types')->assertNotFound();
    }

    #[Test]
    public function show_returns_404_when_ticket_type_does_not_belong_to_the_event(): void
    {
        $event = Event::factory()->published()->create();
        $ticketType = TicketType::factory()->active()->for(Event::factory()->published())->create();

        $this->getJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types/'.$ticketType->slug)
            ->assertNotFound();
    }

    #[Test]
    public function store_returns_403_when_user_has_no_catalog_permission(): void
    {
        $event = Event::factory()->published()->create();
        $user = $this->userWithRole(UserRole::Customer);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types', $this->ticketTypePayload())
            ->assertForbidden();
    }

    #[Test]
    public function store_returns_422_when_slug_is_already_used_for_the_event(): void
    {
        $event = Event::factory()->published()->create();
        TicketType::factory()->for($event)->create(['slug' => 'standard']);
        $user = $this->userWithRole(UserRole::CatalogManager);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types', $this->ticketTypePayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.pointer', '#/slug');
    }

    #[Test]
    public function store_allows_the_same_slug_for_different_events(): void
    {
        $event = Event::factory()->published()->create();
        TicketType::factory()->for(Event::factory()->published())->create(['slug' => 'standard']);
        $user = $this->userWithRole(UserRole::CatalogManager);
        $payload = $this->ticketTypePayload();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types', $payload)
            ->assertCreated()
            ->assertJsonPath('data.slug', $payload['slug'])
            ->assertJsonPath('data.status', TicketTypeStatus::Active->value);

        $this->assertDatabaseHas('ticket_types', [
            'event_id' => $event->id,
            'slug' => $payload['slug'],
            'status' => TicketTypeStatus::Active->value,
        ]);
    }

    #[Test]
    public function update_persists_changes_for_a_catalog_manager(): void
    {
        $event = Event::factory()->published()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $user = $this->userWithRole(UserRole::CatalogManager);
        $payload = $this->ticketTypePayload([
            'slug' => 'premium',
            'name' => 'Premium ticket',
            'status' => TicketTypeStatus::Disabled->value,
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->patchJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types/'.$ticketType->slug, $payload)
            ->assertOk()
            ->assertJsonPath('data.name', 'Premium ticket')
            ->assertJsonPath('data.status', TicketTypeStatus::Disabled->value);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'slug' => 'premium',
            'status' => TicketTypeStatus::Disabled->value,
        ]);
    }

    #[Test]
    public function destroy_removes_a_ticket_type_for_a_catalog_manager(): void
    {
        $event = Event::factory()->published()->create();
        $ticketType = TicketType::factory()->for($event)->create();
        $user = $this->userWithRole(UserRole::CatalogManager);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/catalog/events/'.$event->slug.'/ticket-types/'.$ticketType->slug)
            ->assertNoContent();

        $this->assertDatabaseMissing('ticket_types', ['id' => $ticketType->id]);
    }

    /**
     * @param array<string, string|int> $overrides
     * @return array<string, string|int>
     */
    private function ticketTypePayload(array $overrides = []): array
    {
        return array_replace([
            'slug' => 'standard',
            'name' => 'Standard ticket',
            'price_amount' => 2_000,
            'currency' => Currency::Rub->value,
            'capacity' => 100,
            'status' => TicketTypeStatus::Active->value,
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
