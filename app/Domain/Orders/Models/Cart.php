<?php

declare(strict_types=1);

namespace App\Domain\Orders\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Orders\Enums\CartStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'status', 'expires_at'])]
/**
 * @property string $id
 * @property int $user_id
 * @property CartStatus $status
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 *
 * @method static Builder<Cart> active()
 */
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** @param Builder<Cart> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query
            ->where('status', CartStatus::Active)
            ->where('expires_at', '>', now());
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'status' => CartStatus::class,
        ];
    }

    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }
}
