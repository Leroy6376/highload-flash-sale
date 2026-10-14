<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\OrderConflictException;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\TicketAllocationService;
use Illuminate\Support\Facades\DB;

final readonly class CheckoutCartAction
{
    public function __construct(private TicketAllocationService $ticketAllocation) {}

    public function handle(User $user): Order
    {
        return DB::transaction(function () use ($user): Order {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $cart = Cart::active()->whereBelongsTo($user)->with('items')->lockForUpdate()->first();
            if ($cart === null || $cart->items->isEmpty()) {
                throw new OrderConflictException('The cart is empty or has expired.');
            }

            $ticketTypes = TicketType::query()
                ->with('event')
                ->whereKey($cart->items->pluck('ticket_type_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $currency = null;
            $totalAmount = 0;
            foreach ($cart->items as $line) {
                /** @var TicketType|null $ticketType */
                $ticketType = $ticketTypes->get($line->ticket_type_id);
                if ($ticketType === null) {
                    throw new OrderConflictException('A ticket type is no longer available.');
                }
                $this->ticketAllocation->assertReservationCanBeCheckedOut($ticketType);
                $currency ??= $ticketType->currency;
                $totalAmount += $ticketType->price_amount * $line->quantity;
            }

            assert($currency !== null);
            $order = Order::create([
                'currency' => $currency,
                'expires_at' => now()->addMinutes(config()->integer('orders.reservation_minutes')),
                'status' => OrderStatus::PendingPayment,
                'total_amount' => $totalAmount,
                'user_id' => $user->id,
            ]);
            foreach ($cart->items as $cartItem) {
                /** @var TicketType $ticketType */
                $ticketType = $ticketTypes->get($cartItem->ticket_type_id);
                $order->items()->create([
                    'currency' => $ticketType->currency,
                    'quantity' => $cartItem->quantity,
                    'ticket_type_id' => $ticketType->id,
                    'ticket_type_name' => $ticketType->name,
                    'unit_price_amount' => $ticketType->price_amount,
                ]);
            }
            $cart->update(['status' => CartStatus::CheckedOut]);

            return $order->load('items');
        });
    }
}
