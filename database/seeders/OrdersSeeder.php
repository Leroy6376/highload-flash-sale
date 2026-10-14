<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Cart;
use App\Domain\Orders\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $ticketTypes = TicketType::query()->whereIn('slug', [
                'standard',
                'premium',
                'base',
                'lecture',
            ])->get()->keyBy('slug');

            $standard = $this->ticketType($ticketTypes, 'standard');
            $premium = $this->ticketType($ticketTypes, 'premium');
            $base = $this->ticketType($ticketTypes, 'base');
            $lecture = $this->ticketType($ticketTypes, 'lecture');

            $this->seedCart(
                $this->customer(CustomerSeeder::ActiveCartEmail),
                CartStatus::Active,
                now()->addMinutes(12),
                [$standard->id => 2, $premium->id => 1],
            );
            $this->seedCart(
                $this->customer(CustomerSeeder::ExpiredCartEmail),
                CartStatus::Expired,
                now()->subMinutes(5),
                [$base->id => 1],
            );

            $this->seedOrder(
                $this->customer(CustomerSeeder::PendingOrderEmail),
                OrderStatus::PendingPayment,
                now()->addMinutes(10),
                null,
                [$premium->id => 1],
            );
            $this->seedOrder(
                $this->customer(CustomerSeeder::PaidOrderEmail),
                OrderStatus::Paid,
                null,
                now()->subDay(),
                [$standard->id => 2, $base->id => 1],
            );
            $this->seedOrder(
                $this->customer(CustomerSeeder::CancelledOrderEmail),
                OrderStatus::Cancelled,
                null,
                now()->subHours(6),
                [$lecture->id => 1],
            );
            $this->seedOrder(
                $this->customer(CustomerSeeder::ExpiredOrderEmail),
                OrderStatus::Expired,
                now()->subMinutes(10),
                now()->subMinutes(5),
                [$lecture->id => 2],
            );
        });
    }

    private function customer(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    /** @param array<string, int> $items */
    private function seedCart(User $user, CartStatus $status, CarbonInterface $expiresAt, array $items): void
    {
        $cart = Cart::query()->updateOrCreate(
            ['status' => $status, 'user_id' => $user->id],
            ['expires_at' => $expiresAt],
        );
        foreach ($cart->items()->get() as $cartItem) {
            $cartItem->delete();
        }

        foreach ($items as $ticketTypeId => $quantity) {
            $cart->items()->create([
                'quantity' => $quantity,
                'ticket_type_id' => $ticketTypeId,
            ]);
        }
    }

    /** @param array<string, int> $items */
    private function seedOrder(
        User $user,
        OrderStatus $status,
        ?CarbonInterface $expiresAt,
        ?CarbonInterface $completedAt,
        array $items,
    ): void {
        $ticketTypes = TicketType::query()->whereKey(array_keys($items))->get()->keyBy('id');
        $totalAmount = 0;
        foreach ($items as $ticketTypeId => $quantity) {
            $ticketType = $this->ticketType($ticketTypes, $ticketTypeId);
            $totalAmount += $ticketType->price_amount * $quantity;
        }

        $order = Order::query()->updateOrCreate(
            ['status' => $status, 'user_id' => $user->id],
            [
                'cancelled_at' => $status === OrderStatus::Cancelled ? $completedAt : null,
                'currency' => Currency::Rub,
                'expired_at' => $status === OrderStatus::Expired ? $completedAt : null,
                'expires_at' => $expiresAt,
                'paid_at' => $status === OrderStatus::Paid ? $completedAt : null,
                'total_amount' => $totalAmount,
            ],
        );
        foreach ($order->items()->get() as $orderItem) {
            $orderItem->delete();
        }

        foreach ($items as $ticketTypeId => $quantity) {
            $ticketType = $this->ticketType($ticketTypes, $ticketTypeId);
            $order->items()->create([
                'currency' => $ticketType->currency,
                'quantity' => $quantity,
                'ticket_type_id' => $ticketType->id,
                'ticket_type_name' => $ticketType->name,
                'unit_price_amount' => $ticketType->price_amount,
            ]);
        }
    }

    /** @param Collection<int|string, TicketType> $ticketTypes */
    private function ticketType(Collection $ticketTypes, string $key): TicketType
    {
        $ticketType = $ticketTypes->get($key);
        if (! $ticketType instanceof TicketType) {
            throw new \RuntimeException("The demo ticket type [{$key}] was not found.");
        }

        return $ticketType;
    }
}
