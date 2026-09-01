<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Models;

use App\Domain\Shared\Images\Enums\ImageCollection;
use Database\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['collection', 'path', 'alt_text', 'sort_order'])]
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory, HasUuids;

    /** @return MorphTo<Model, $this> */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'collection' => ImageCollection::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): ImageFactory
    {
        return ImageFactory::new();
    }
}
