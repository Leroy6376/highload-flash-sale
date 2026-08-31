<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImageCollection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['collection', 'path', 'alt_text', 'sort_order'])]
class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
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
}
