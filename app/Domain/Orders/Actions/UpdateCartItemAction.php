<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Exceptions\OrderConflictException;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\CartItem;
use App\Domain\Orders\Services\TicketAllocationService;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCartItemAction
{
    public function __construct(private TicketAllocationService $ticketAllocation) {}

    public function handle(User $user, CartItem $cartItem, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $cartItem, $quantity): Cart {
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

            $ticketType = TicketType::query()->with('event')->lockForUpdate()->findOrFail($cartItem->ticket_type_id);
            $this->ticketAllocation->assertCanReserve($ticketType, $user, $quantity, $cartItem);
            $cartItem->update(['quantity' => $quantity]);
            $cart->update(['expires_at' => $this->reservationExpiresAt()]);

            return $cart->refresh()->load('items.ticketType');
        });
    }

    private function reservationExpiresAt(): \Carbon\CarbonInterface
    {
        return now()->addMinutes(config()->integer('orders.reservation_minutes'));
    }
}
