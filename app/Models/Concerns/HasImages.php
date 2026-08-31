<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\ImageCollection;
use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/** @mixin Model */
trait HasImages
{
    /** @return MorphMany<Image, $this> */
    public function images(): MorphMany
    {
        $images = $this->morphMany(Image::class, 'imageable');

        $images->getBaseQuery()->orderBy('sort_order')->orderBy('id');

        return $images;
    }

    /** @return MorphOne<Image, $this> */
    public function announcementImage(): MorphOne
    {
        $image = $this->morphOne(Image::class, 'imageable');

        $image->getBaseQuery()->where('collection', ImageCollection::Announcement->value);

        return $image;
    }

    /** @return MorphMany<Image, $this> */
    public function galleryImages(): MorphMany
    {
        $images = $this->morphMany(Image::class, 'imageable');

        $images->getBaseQuery()
            ->where('collection', ImageCollection::Gallery->value)
            ->orderBy('sort_order')
            ->orderBy('id');

        return $images;
    }

    public static function bootHasImages(): void
    {
        static::deleting(function (self $model): void {
            $model->images()->getBaseQuery()->delete();
        });
    }
}
