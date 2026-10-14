<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Models\Order;

final class ShowOrderAction
{
    public function handle(User $user, Order $order): Order
    {
        return Order::query()->whereBelongsTo($user)->whereKey($order)->with('items')->firstOrFail();
    }
}
