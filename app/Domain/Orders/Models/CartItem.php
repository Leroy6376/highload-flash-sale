<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\TicketType;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_type_id', 'quantity'])]
/**
 * @property string $id
 * @property string $cart_id
 * @property string $ticket_type_id
 * @property int $quantity
 */
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Cart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }
}
