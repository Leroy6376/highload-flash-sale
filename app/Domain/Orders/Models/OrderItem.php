<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Models\TicketType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_type_id',
    'ticket_type_name',
    'unit_price_amount',
    'currency',
    'quantity',
])]
/**
 * @property string $id
 * @property string $order_id
 * @property string $ticket_type_id
 * @property string $ticket_type_name
 * @property int $unit_price_amount
 * @property Currency $currency
 * @property int $quantity
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
        ];
    }

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }
}
