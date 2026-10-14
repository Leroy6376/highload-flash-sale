<?php

declare(strict_types=1);

namespace App\Domain\Orders\Services;

use App\Domain\Catalog\Contracts\PendingAllocationInvalidator;
use App\Domain\Catalog\Contracts\TicketAllocationReader;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\OrderConflictException;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\CartItem;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;

final class TicketAllocationService implements PendingAllocationInvalidator, TicketAllocationReader
{
    public function allocatedQuantity(TicketType $ticketType): int
    {
        return $this->cartQuantity($ticketType)
            + $this->pendingOrderQuantity($ticketType)
            + $this->paidOrderQuantity($ticketType);
    }

    public function assertCanReserve(
        TicketType $ticketType,
        User $user,
        int $quantity,
        ?CartItem $existingCartItem = null,
    ): void {
        $this->assertSaleIsOpen($ticketType);

        $existingQuantity = $existingCartItem === null ? 0 : $existingCartItem->quantity;
        $allocatedQuantity = $this->allocatedQuantity($ticketType) - $existingQuantity + $quantity;
        if ($allocatedQuantity > $ticketType->capacity) {
            throw new OrderConflictException('There are not enough tickets available.');
        }

        if ($ticketType->sales_limit_per_user === null) {
            return;
        }

        $userQuantity = $this->allocatedQuantityForUser($ticketType, $user) - $existingQuantity + $quantity;
        if ($userQuantity > $ticketType->sales_limit_per_user) {
            throw new OrderConflictException('The ticket limit per user has been exceeded.');
        }
    }

    public function assertReservationCanBeCheckedOut(TicketType $ticketType): void
    {
        $event = $ticketType->event()->firstOrFail();
        if ($event->status !== EventStatus::Published || $ticketType->status !== TicketTypeStatus::Active) {
            throw new OrderConflictException('The ticket type is no longer available.');
        }
    }

    public function expireForEvent(Event $event): void
    {
        $ticketTypeIds = TicketType::query()->whereBelongsTo($event)->select('id');

        $this->expireCarts(Cart::query()->whereIn('id', CartItem::query()->whereIn('ticket_type_id', $ticketTypeIds)->select('cart_id')));
        $this->expireOrders(Order::query()->whereIn('id', OrderItem::query()->whereIn('ticket_type_id', $ticketTypeIds)->select('order_id')));
    }

    public function expireForTicketType(TicketType $ticketType): void
    {
        $this->expireCarts(Cart::query()->whereIn('id', CartItem::query()->whereBelongsTo($ticketType, 'ticketType')->select('cart_id')));
        $this->expireOrders(Order::query()->whereIn('id', OrderItem::query()->whereBelongsTo($ticketType, 'ticketType')->select('order_id')));
    }

    public function expireStaleReservations(): void
    {
        $now = now();

        Cart::query()->where('status', CartStatus::Active)->where('expires_at', '<=', $now)->update(['status' => CartStatus::Expired]);
        Order::query()->where('status', OrderStatus::PendingPayment)->where('expires_at', '<=', $now)->update([
            'expired_at' => $now,
            'status' => OrderStatus::Expired,
        ]);
    }

    public function hasOrderHistoryForEvent(Event $event): bool
    {
        return OrderItem::query()->whereIn(
            'ticket_type_id',
            TicketType::query()->whereBelongsTo($event)->select('id'),
        )->exists();
    }

    public function hasOrderHistoryForTicketType(TicketType $ticketType): bool
    {
        return OrderItem::query()->whereBelongsTo($ticketType, 'ticketType')->exists();
    }

    private function assertSaleIsOpen(TicketType $ticketType): void
    {
        $event = $ticketType->event()->firstOrFail();
        $now = now();

        if ($event->status !== EventStatus::Published) {
            throw new OrderConflictException('The event is not published.');
        }
        if ($ticketType->status !== TicketTypeStatus::Active) {
            throw new OrderConflictException('The ticket type is not active.');
        }
        if ($event->ends_at !== null && $event->ends_at <= $now) {
            throw new OrderConflictException('The event has already ended.');
        }
        foreach ([$event->sales_starts_at, $ticketType->sales_starts_at] as $startsAt) {
            if ($startsAt !== null && $startsAt > $now) {
                throw new OrderConflictException('Ticket sales have not started yet.');
            }
        }
        foreach ([$event->sales_ends_at, $ticketType->sales_ends_at] as $endsAt) {
            if ($endsAt !== null && $endsAt <= $now) {
                throw new OrderConflictException('Ticket sales have ended.');
            }
        }
    }

    private function allocatedQuantityForUser(TicketType $ticketType, User $user): int
    {
        $cartQuantity = CartItem::query()
            ->whereBelongsTo($ticketType, 'ticketType')
            ->whereIn('cart_id', Cart::active()->whereBelongsTo($user)->select('id'))
            ->sum('quantity');
        $pendingOrderQuantity = OrderItem::query()
            ->whereBelongsTo($ticketType, 'ticketType')
            ->whereIn('order_id', Order::reserving()->whereBelongsTo($user)->select('id'))
            ->sum('quantity');
        $paidOrderQuantity = OrderItem::query()
            ->whereBelongsTo($ticketType, 'ticketType')
            ->whereIn('order_id', Order::query()->whereBelongsTo($user)->where('status', OrderStatus::Paid)->select('id'))
            ->sum('quantity');

        return (int) $cartQuantity + (int) $pendingOrderQuantity + (int) $paidOrderQuantity;
    }

    private function cartQuantity(TicketType $ticketType): int
    {
        return (int) CartItem::query()
            ->whereBelongsTo($ticketType, 'ticketType')
            ->whereIn('cart_id', Cart::active()->select('id'))
            ->sum('quantity');
    }

    private function pendingOrderQuantity(TicketType $ticketType): int
    {
        return (int) OrderItem::query()
            ->whereBelongsTo($ticketType, 'ticketType')
            ->whereIn('order_id', Order::reserving()->select('id'))
            ->sum('quantity');
    }

    private function paidOrderQuantity(TicketType $ticketType): int
    {
        return (int) OrderItem::query()
            ->whereBelongsTo($ticketType, 'ticketType')
            ->whereIn('order_id', Order::query()->where('status', OrderStatus::Paid)->select('id'))
            ->sum('quantity');
    }

    /** @param Builder<Cart> $query */
    private function expireCarts(Builder $query): void
    {
        $query->where('status', CartStatus::Active)->update(['status' => CartStatus::Expired]);
    }

    /** @param Builder<Order> $query */
    private function expireOrders(Builder $query): void
    {
        $now = now();
        $query->where('status', OrderStatus::PendingPayment)->update([
            'expired_at' => $now,
            'status' => OrderStatus::Expired,
        ]);
    }
}
