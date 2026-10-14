<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Models\Cart;

final class GetCurrentCartAction
{
    public function handle(User $user): ?Cart
    {
        return Cart::active()
            ->whereBelongsTo($user)
            ->with('items.ticketType')
            ->first();
    }
}
