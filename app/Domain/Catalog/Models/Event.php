<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\EventStatus;
use App\Models\Concerns\HasImages;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'title',
    'short_description',
    'description',
    'timezone',
    'starts_at',
    'ends_at',
    'sales_starts_at',
    'sales_ends_at',
    'status',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasImages, HasUuids;

    /** @return HasMany<TicketType, $this> */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
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

    protected static function booted(): void
    {
        static::deleting(function (self $event): void {
            foreach ($event->ticketTypes()->get() as $ticketType) {
                $ticketType->delete();
            }
        });
    }
}
