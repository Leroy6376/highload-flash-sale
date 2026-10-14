<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Orders\Actions;

use App\Domain\Orders\Actions\ExpireReservationsAction;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpireReservationsActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function expires_only_stale_active_carts_and_pending_orders(): void
    {
        $this->freezeTime();
        $expiredCart = Cart::factory()->create(['expires_at' => now(), 'status' => CartStatus::Active]);
        $activeCart = Cart::factory()->create(['expires_at' => now()->addSecond(), 'status' => CartStatus::Active]);
        $expiredOrder = Order::factory()->create(['expires_at' => now(), 'status' => OrderStatus::PendingPayment]);
        $pendingOrder = Order::factory()->create(['expires_at' => now()->addSecond(), 'status' => OrderStatus::PendingPayment]);
        $paidOrder = Order::factory()->create(['expires_at' => null, 'status' => OrderStatus::Paid]);

        app(ExpireReservationsAction::class)->handle();

        $this->assertDatabaseHas('carts', ['id' => $expiredCart->id, 'status' => CartStatus::Expired->value]);
        $this->assertDatabaseHas('carts', ['id' => $activeCart->id, 'status' => CartStatus::Active->value]);
        $this->assertDatabaseHas('orders', ['id' => $expiredOrder->id, 'status' => OrderStatus::Expired->value]);
        $this->assertDatabaseHas('orders', ['id' => $pendingOrder->id, 'status' => OrderStatus::PendingPayment->value]);
        $this->assertDatabaseHas('orders', ['id' => $paidOrder->id, 'status' => OrderStatus::Paid->value]);
    }
}
