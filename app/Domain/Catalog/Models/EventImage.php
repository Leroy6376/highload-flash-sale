<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\ImageCollection;
use Database\Factories\EventImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['collection', 'path', 'alt_text', 'sort_order'])]
class EventImage extends Model
{
    /** @use HasFactory<EventImageFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected function casts(): array
    {
        return [
            'collection' => ImageCollection::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): EventImageFactory
    {
        return EventImageFactory::new();
    }
}
