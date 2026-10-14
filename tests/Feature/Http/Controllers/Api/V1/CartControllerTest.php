<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\CartItem;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    #[DataProvider('protectedEndpoints')]
    public function requests_return_401_when_no_token_is_provided(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized();
    }

    #[Test]
    public function show_returns_null_when_user_has_no_active_cart(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/cart')->assertOk()->assertJsonPath('data', null);
    }

    #[Test]
    public function show_returns_only_the_authenticated_users_active_cart(): void
    {
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType();
        $cart = Cart::factory()->for($user)->create();
        CartItem::factory()->for($cart)->for($ticketType, 'ticketType')->create(['quantity' => 2]);
        Cart::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/cart')
            ->assertOk()->assertJsonPath('data.id', $cart->id)
            ->assertJsonPath('data.items.0.ticket_type_id', $ticketType->id)
            ->assertJsonPath('data.items.0.quantity', 2);
    }

    #[Test]
    public function store_creates_an_active_cart_and_reserves_tickets(): void
    {
        $this->travelTo('2026-10-12 12:00:00');
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['ticket_type_id' => $ticketType->id, 'quantity' => 2])
            ->assertCreated()->assertJsonPath('data.status', CartStatus::Active->value)
            ->assertJsonPath('data.expires_at', '2026-10-12T12:15:00.000000Z')
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertDatabaseHas('carts', ['status' => CartStatus::Active->value, 'user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', ['quantity' => 2, 'ticket_type_id' => $ticketType->id]);
    }

    #[Test]
    public function store_increases_an_existing_item_and_extends_the_reservation(): void
    {
        $this->travelTo('2026-10-12 12:00:00');
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType();
        $cart = Cart::factory()->for($user)->create(['expires_at' => now()->addMinutes(5)]);
        $cartItem = CartItem::factory()->for($cart)->for($ticketType, 'ticketType')->create(['quantity' => 1]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['ticket_type_id' => $ticketType->id, 'quantity' => 2])
            ->assertOk()->assertJsonPath('data.id', $cart->id)
            ->assertJsonPath('data.expires_at', '2026-10-12T12:15:00.000000Z')
            ->assertJsonPath('data.items.0.id', $cartItem->id)->assertJsonPath('data.items.0.quantity', 3);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 3]);
    }

    /**
     * @param array<string, mixed> $eventAttributes
     * @param array<string, mixed> $ticketTypeAttributes
     */
    #[Test]
    #[DataProvider('unavailableTicketTypes')]
    public function store_returns_409_when_ticket_type_is_not_available(array $eventAttributes, array $ticketTypeAttributes, string $detail): void
    {
        $this->travelTo('2026-10-12 12:00:00');
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType($eventAttributes, $ticketTypeAttributes);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['ticket_type_id' => $ticketType->id, 'quantity' => 1])
            ->assertConflict()->assertJsonPath('detail', $detail);

        $this->assertDatabaseCount('carts', 0);
    }

    #[Test]
    public function store_returns_409_when_global_capacity_is_exhausted(): void
    {
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType([], ['capacity' => 2]);
        $otherCart = Cart::factory()->create();
        CartItem::factory()->for($otherCart)->for($ticketType, 'ticketType')->create(['quantity' => 2]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['ticket_type_id' => $ticketType->id, 'quantity' => 1])
            ->assertConflict()->assertJsonPath('detail', 'There are not enough tickets available.');

        $this->assertDatabaseCount('carts', 1);
    }

    #[Test]
    public function store_returns_409_when_user_ticket_limit_is_exceeded_by_paid_tickets(): void
    {
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType([], ['sales_limit_per_user' => 2]);
        $order = Order::factory()->for($user)->create(['expires_at' => null, 'status' => OrderStatus::Paid]);
        OrderItem::factory()->for($order)->for($ticketType, 'ticketType')->create(['quantity' => 2]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/items', ['ticket_type_id' => $ticketType->id, 'quantity' => 1])
            ->assertConflict()->assertJsonPath('detail', 'The ticket limit per user has been exceeded.');

        $this->assertDatabaseCount('carts', 0);
    }

    /** @param array<string, mixed> $payload */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function store_returns_422_for_an_invalid_payload(array $payload, string $pointer): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')->postJson('/api/v1/cart/items', $payload)
            ->assertUnprocessable()->assertJsonPath('errors.0.pointer', $pointer);
    }

    #[Test]
    public function update_changes_an_owned_active_cart_item_and_extends_the_reservation(): void
    {
        $this->travelTo('2026-10-12 12:00:00');
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType();
        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->for($ticketType, 'ticketType')->create(['quantity' => 1]);

        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/cart/items/'.$cartItem->id, ['quantity' => 3])
            ->assertOk()->assertJsonPath('data.expires_at', '2026-10-12T12:15:00.000000Z')
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 3]);
    }

    #[Test]
    public function update_returns_409_and_does_not_change_another_users_cart_item(): void
    {
        $ticketType = $this->saleableTicketType();
        $cart = Cart::factory()->create();
        $cartItem = CartItem::factory()->for($cart)->for($ticketType, 'ticketType')->create(['quantity' => 1]);

        $this->actingAs(User::factory()->create(), 'sanctum')->patchJson('/api/v1/cart/items/'.$cartItem->id, ['quantity' => 2])
            ->assertConflict()->assertJsonPath('detail', 'The cart item is no longer active.');

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 1]);
    }

    #[Test]
    public function destroy_removes_an_owned_active_cart_item(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/cart/items/'.$cartItem->id)->assertNoContent();

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    #[Test]
    public function destroy_returns_409_and_does_not_remove_another_users_cart_item(): void
    {
        $cartItem = CartItem::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')->deleteJson('/api/v1/cart/items/'.$cartItem->id)->assertConflict();

        $this->assertModelExists($cartItem);
    }

    #[Test]
    public function checkout_creates_an_order_snapshot_and_marks_cart_as_checked_out(): void
    {
        $this->travelTo('2026-10-12 12:00:00');
        $user = User::factory()->create();
        $ticketType = $this->saleableTicketType([], ['name' => 'Premium', 'price_amount' => 2_500]);
        $cart = Cart::factory()->for($user)->create();
        CartItem::factory()->for($cart)->for($ticketType, 'ticketType')->create(['quantity' => 2]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/checkout')
            ->assertCreated()->assertJsonPath('data.status', OrderStatus::PendingPayment->value)
            ->assertJsonPath('data.total_amount', 5_000)->assertJsonPath('data.expires_at', '2026-10-12T12:15:00.000000Z')
            ->assertJsonPath('data.items.0.ticket_type_name', 'Premium')->assertJsonPath('data.items.0.unit_price_amount', 2_500);

        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'status' => CartStatus::CheckedOut->value]);
        $this->assertDatabaseHas('orders', ['status' => OrderStatus::PendingPayment->value, 'total_amount' => 5_000, 'user_id' => $user->id]);
    }

    #[Test]
    public function checkout_returns_409_for_an_empty_or_expired_cart(): void
    {
        $user = User::factory()->create();
        Cart::factory()->for($user)->create(['expires_at' => now()->subSecond()]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/cart/checkout')
            ->assertConflict()->assertJsonPath('detail', 'The cart is empty or has expired.');

        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * @return array<string, array{
     *     0: string,
     *     1: string,
     * }>
     */
    public static function protectedEndpoints(): array
    {
        return [
            'show cart' => [
                'GET',
                '/api/v1/cart',
            ],
            'add item' => [
                'POST',
                '/api/v1/cart/items',
            ],
            'update item' => [
                'PATCH',
                '/api/v1/cart/items/00000000-0000-0000-0000-000000000000',
            ],
            'remove item' => [
                'DELETE',
                '/api/v1/cart/items/00000000-0000-0000-0000-000000000000',
            ],
            'checkout' => [
                'POST',
                '/api/v1/cart/checkout',
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     0: array<string, mixed>,
     *     1: array<string, mixed>,
     *     2: string,
     * }>
     */
    public static function unavailableTicketTypes(): array
    {
        return [
            'event is not published' => [
                ['status' => EventStatus::Draft],
                [],
                'The event is not published.',
            ],
            'ticket type is disabled' => [
                [],
                ['status' => TicketTypeStatus::Disabled],
                'The ticket type is not active.',
            ],
            'event has ended' => [
                ['ends_at' => '2026-10-12 11:59:59'],
                [],
                'The event has already ended.',
            ],
            'event sales have not started' => [
                ['sales_starts_at' => '2026-10-12 12:00:01'],
                [],
                'Ticket sales have not started yet.',
            ],
            'ticket sales have not started' => [
                [],
                ['sales_starts_at' => '2026-10-12 12:00:01'],
                'Ticket sales have not started yet.',
            ],
            'event sales have ended' => [
                ['sales_ends_at' => '2026-10-12 12:00:00'],
                [],
                'Ticket sales have ended.',
            ],
            'ticket sales have ended' => [
                [],
                ['sales_ends_at' => '2026-10-12 12:00:00'],
                'Ticket sales have ended.',
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     0: array<string, mixed>,
     *     1: string,
     * }>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing fields' => [
                [],
                '#/quantity',
            ],
            'quantity is zero' => [
                ['ticket_type_id' => '00000000-0000-0000-0000-000000000000', 'quantity' => 0],
                '#/quantity',
            ],
            'ticket type is not a uuid' => [
                ['ticket_type_id' => 'not-a-uuid', 'quantity' => 1],
                '#/ticket_type_id',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $eventAttributes
     * @param array<string, mixed> $ticketTypeAttributes
     */
    private function saleableTicketType(array $eventAttributes = [], array $ticketTypeAttributes = []): TicketType
    {
        $event = Event::factory()->published()->create(array_replace([
            'ends_at' => now()->addDay(),
            'sales_ends_at' => now()->addHour(),
            'sales_starts_at' => now()->subHour(),
            'starts_at' => now()->addHours(2),
        ], $eventAttributes));

        return TicketType::factory()->active()->for($event)->create(array_replace([
            'capacity' => 10,
            'sales_ends_at' => now()->addHour(),
            'sales_limit_per_user' => null,
            'sales_starts_at' => now()->subHour(),
        ], $ticketTypeAttributes));
    }
}
