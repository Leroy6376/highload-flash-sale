<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Data\CartItemData;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\CartItem;
use App\Domain\Orders\Services\TicketAllocationService;
use Illuminate\Support\Facades\DB;

final readonly class AddCartItemAction
{
    public function __construct(private TicketAllocationService $ticketAllocation) {}

    public function handle(User $user, CartItemData $data): Cart
    {
        return DB::transaction(function () use ($user, $data): Cart {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $now = now();
            Cart::query()
                ->whereBelongsTo($user)
                ->where('status', CartStatus::Active)
                ->where('expires_at', '<=', $now)
                ->update(['status' => CartStatus::Expired]);

            $cart = Cart::active()->whereBelongsTo($user)->first();
            $cart ??= Cart::create([
                'expires_at' => $this->reservationExpiresAt(),
                'status' => CartStatus::Active,
                'user_id' => $user->id,
            ]);

            $ticketType = TicketType::query()->with('event')->lockForUpdate()->findOrFail($data->ticketTypeId);
            $cartItem = CartItem::query()
                ->whereBelongsTo($cart)
                ->whereBelongsTo($ticketType, 'ticketType')
                ->first();
            $quantity = ($cartItem === null ? 0 : $cartItem->quantity) + $data->quantity;
            $this->ticketAllocation->assertCanReserve($ticketType, $user, $quantity, $cartItem);

            if ($cartItem === null) {
                $cart->items()->create([
                    'quantity' => $quantity,
                    'ticket_type_id' => $ticketType->id,
                ]);
            } else {
                $cartItem->update(['quantity' => $quantity]);
            }
            $cart->update(['expires_at' => $this->reservationExpiresAt()]);

            return $cart->refresh()->load('items.ticketType');
        });
    }

    private function reservationExpiresAt(): \Carbon\CarbonInterface
    {
        return now()->addMinutes(config()->integer('orders.reservation_minutes'));
    }
}
