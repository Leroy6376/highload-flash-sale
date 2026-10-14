<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function index_returns_only_authenticated_users_orders_in_descending_order(): void
    {
        $user = User::factory()->create();
        $oldOrder = Order::factory()->for($user)->create(['created_at' => now()->subMinute()]);
        $newOrder = Order::factory()->for($user)->create();
        Order::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/orders?per_page=1')
            ->assertOk()->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $newOrder->id)->assertJsonMissing(['id' => $oldOrder->id]);
    }

    #[Test]
    public function index_returns_422_for_an_invalid_page_size(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/v1/orders?per_page=101')
            ->assertUnprocessable()->assertJsonPath('errors.0.pointer', '#/per_page');
    }

    #[Test]
    public function show_returns_404_for_another_users_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')->getJson('/api/v1/orders/'.$order->id)->assertNotFound();
    }

    #[Test]
    public function cancel_changes_an_active_pending_order_to_cancelled(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders/'.$order->id.'/cancellations')
            ->assertOk()->assertJsonPath('data.status', OrderStatus::Cancelled->value)
            ->assertJsonPath('data.expires_at', null)->assertJsonPath('data.cancelled_at', now()->startOfSecond()->toISOString());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value]);
    }

    #[Test]
    public function cancel_returns_409_without_changing_an_expired_pending_order(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['expires_at' => now()]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders/'.$order->id.'/cancellations')
            ->assertConflict()->assertJsonPath('detail', 'The order has expired.');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::PendingPayment->value]);
    }

    #[Test]
    public function payment_confirmation_returns_403_without_permission(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')->postJson('/api/v1/orders/'.$order->id.'/payment-confirmations')
            ->assertForbidden();
    }

    #[Test]
    public function payment_confirmation_marks_an_available_order_as_paid(): void
    {
        $this->freezeTime();
        $this->seed(RolesAndPermissionsSeeder::class);
        $operator = User::factory()->create();
        $operator->givePermissionTo('orders.payment-confirm');
        $event = Event::factory()->published()->create(['ends_at' => now()->addDay()]);
        $ticketType = TicketType::factory()->active()->for($event)->create();
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->for($ticketType, 'ticketType')->create();

        $this->actingAs($operator, 'sanctum')->postJson('/api/v1/orders/'.$order->id.'/payment-confirmations')
            ->assertOk()->assertJsonPath('data.status', OrderStatus::Paid->value)
            ->assertJsonPath('data.expires_at', null)->assertJsonPath('data.paid_at', now()->startOfSecond()->toISOString());

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Paid->value]);
    }

    #[Test]
    public function payment_confirmation_returns_409_without_changing_an_expired_order(): void
    {
        $this->freezeTime();
        $this->seed(RolesAndPermissionsSeeder::class);
        $operator = User::factory()->create();
        $operator->givePermissionTo('orders.payment-confirm');
        $order = Order::factory()->create(['expires_at' => now()]);

        $this->actingAs($operator, 'sanctum')->postJson('/api/v1/orders/'.$order->id.'/payment-confirmations')
            ->assertConflict()->assertJsonPath('detail', 'The order has expired.');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::PendingPayment->value]);
    }
}
