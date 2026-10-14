<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Exceptions\OrderConflictException;
use App\Domain\Orders\Models\CartItem;
use Illuminate\Support\Facades\DB;

final class RemoveCartItemAction
{
    public function handle(User $user, CartItem $cartItem): void
    {
        DB::transaction(function () use ($user, $cartItem): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $cartItem = CartItem::query()->with('cart')->lockForUpdate()->findOrFail($cartItem->id);
            $cart = $cartItem->cart;
            if (
                $cart === null
                || $cart->user_id !== $user->id
                || $cart->status !== CartStatus::Active
                || $cart->expires_at === null
                || $cart->expires_at <= now()
            ) {
                throw new OrderConflictException('The cart item is no longer active.');
            }

            $cartItem->delete();
        });
    }
}
