<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'status',
    'expires_at',
    'total_amount',
    'currency',
    'paid_at',
    'cancelled_at',
    'expired_at',
])]
/**
 * @property string $id
 * @property int $user_id
 * @property OrderStatus $status
 * @property CarbonImmutable|null $expires_at
 * @property int $total_amount
 * @property Currency $currency
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $expired_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 *
 * @method static Builder<Order> reserving()
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @param Builder<Order> $query */
    #[Scope]
    protected function reserving(Builder $query): void
    {
        $query
            ->where('status', OrderStatus::PendingPayment)
            ->where('expires_at', '>', now());
    }

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'immutable_datetime',
            'currency' => Currency::class,
            'expired_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'status' => OrderStatus::class,
            'total_amount' => 'integer',
        ];
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
