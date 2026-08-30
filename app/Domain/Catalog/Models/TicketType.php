<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\ImageCollection;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<TicketTypeImage, $this> */
    public function images(): HasMany
    {
        /** @phpstan-ignore-next-line */
        return $this->hasMany(TicketTypeImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<TicketTypeImage, $this> */
    public function announcementImage(): HasOne
    {
        /** @phpstan-ignore-next-line */
        return $this->hasOne(TicketTypeImage::class)->where('collection', ImageCollection::Announcement->value);
    }

    /** @return HasMany<TicketTypeImage, $this> */
    public function galleryImages(): HasMany
    {
        /** @phpstan-ignore-next-line */
        return $this->hasMany(TicketTypeImage::class)->where('collection', ImageCollection::Gallery->value)->orderBy('sort_order')->orderBy('id');
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
