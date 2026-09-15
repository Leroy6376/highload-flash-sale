<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Shared\Images\Concerns\HasImages;
use Carbon\CarbonImmutable;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slug',
    'name',
    'description',
    'price_amount',
    'currency',
    'capacity',
    'sales_limit_per_user',
    'sales_starts_at',
    'sales_ends_at',
    'status',
])]
/**
 * @property string $id
 * @property string $event_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $price_amount
 * @property Currency $currency
 * @property int $capacity
 * @property int|null $sales_limit_per_user
 * @property CarbonImmutable|null $sales_starts_at
 * @property CarbonImmutable|null $sales_ends_at
 * @property TicketTypeStatus $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 *
 * @method static Builder<TicketType> active()
 */
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory, HasImages, HasUuids;

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @param Builder<TicketType> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', TicketTypeStatus::Active);
    }

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'currency' => Currency::class,
            'price_amount' => 'integer',
            'sales_ends_at' => 'immutable_datetime',
            'sales_limit_per_user' => 'integer',
            'sales_starts_at' => 'immutable_datetime',
            'status' => TicketTypeStatus::class,
        ];
    }

    protected static function newFactory(): TicketTypeFactory
    {
        return TicketTypeFactory::new();
    }
}
