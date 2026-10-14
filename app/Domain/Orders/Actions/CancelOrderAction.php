<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\OrderConflictException;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

final class CancelOrderAction
{
    public function handle(User $user, Order $order): Order
    {
        return DB::transaction(function () use ($user, $order): Order {
            $order = Order::query()->whereBelongsTo($user)->whereKey($order)->lockForUpdate()->firstOrFail();
            if ($order->status !== OrderStatus::PendingPayment) {
                throw new OrderConflictException('Only pending payment orders can be cancelled.');
            }
            if ($order->expires_at === null || $order->expires_at <= now()) {
                $order->update([
                    'expired_at' => now(),
                    'status' => OrderStatus::Expired,
                ]);
                throw new OrderConflictException('The order has expired.');
            }

            $order->update([
                'cancelled_at' => now(),
                'expires_at' => null,
                'status' => OrderStatus::Cancelled,
            ]);

            return $order->refresh()->load('items');
        });
    }
}
