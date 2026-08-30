<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Enums\ImageCollection;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'slug',
    'title',
    'short_description',
    'description',
    'starts_at',
    'ends_at',
    'sales_starts_at',
    'sales_ends_at',
    'status',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    /** @return HasMany<TicketType, $this> */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /** @return HasMany<EventImage, $this> */
    public function images(): HasMany
    {
        /** @phpstan-ignore-next-line */
        return $this->hasMany(EventImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<EventImage, $this> */
    public function announcementImage(): HasOne
    {
        /** @phpstan-ignore-next-line */
        return $this->hasOne(EventImage::class)->where('collection', ImageCollection::Announcement->value);
    }

    /** @return HasMany<EventImage, $this> */
    public function galleryImages(): HasMany
    {
        /** @phpstan-ignore-next-line */
        return $this->hasMany(EventImage::class)->where('collection', ImageCollection::Gallery->value)->orderBy('sort_order')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'sales_starts_at' => 'immutable_datetime',
            'sales_ends_at' => 'immutable_datetime',
            'status' => EventStatus::class,
        ];
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }
}
