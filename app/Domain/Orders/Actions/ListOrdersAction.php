<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrdersAction
{
    /** @return LengthAwarePaginator<int, Order> */
    public function handle(User $user, int $perPage): LengthAwarePaginator
    {
        return Order::query()
            ->whereBelongsTo($user)
            ->with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
