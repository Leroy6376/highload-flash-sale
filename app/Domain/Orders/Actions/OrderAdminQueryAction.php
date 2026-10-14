<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class OrderAdminQueryAction
{
    /** @return Builder<Order> */
    public function handle(): Builder
    {
        return Order::query()->with(['items', 'user'])->orderByDesc('created_at')->orderByDesc('id');
    }
}
