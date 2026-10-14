<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\OrderConflictException;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\TicketAllocationService;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmOrderPaymentAction
{
    public function __construct(private TicketAllocationService $ticketAllocation) {}

    public function handle(Order $order): Order
    {
        $ticketTypeIds = $order->items()->orderBy('ticket_type_id')->pluck('ticket_type_id');

        return DB::transaction(function () use ($order, $ticketTypeIds): Order {
            $ticketTypes = TicketType::query()->with('event')->whereKey($ticketTypeIds)->orderBy('id')->lockForUpdate()->get();
            $order = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== OrderStatus::PendingPayment) {
                throw new OrderConflictException('Only pending payment orders can be paid.');
            }
            if ($order->expires_at === null || $order->expires_at <= now()) {
                $order->update([
                    'expired_at' => now(),
                    'status' => OrderStatus::Expired,
                ]);
                throw new OrderConflictException('The order has expired.');
            }
            foreach ($ticketTypes as $ticketType) {
                $this->ticketAllocation->assertReservationCanBeCheckedOut($ticketType);
            }

            $order->update([
                'expires_at' => null,
                'paid_at' => now(),
                'status' => OrderStatus::Paid,
            ]);

            return $order->refresh()->load('items');
        });
    }
}
