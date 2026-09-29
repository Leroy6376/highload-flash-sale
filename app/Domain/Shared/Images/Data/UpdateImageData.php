<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Data;

use App\Domain\Shared\Images\Enums\ImageCollection;
use Illuminate\Http\UploadedFile;

final readonly class UpdateImageData
{
    public function __construct(
        public ?UploadedFile $file,
        public bool $hasCollection,
        public ?ImageCollection $collection,
        public bool $hasAltText,
        public ?string $altText,
        public bool $hasSortOrder,
        public ?int $sortOrder,
    ) {}

    /** @return array<string, ImageCollection|string|int|null> */
    public function attributes(): array
    {
        $attributes = [];

        if ($this->hasCollection && $this->collection !== null) {
            $attributes['collection'] = $this->collection;
        }

        if ($this->hasAltText) {
            $attributes['alt_text'] = $this->altText;
        }

        if ($this->hasSortOrder && $this->sortOrder !== null) {
            $attributes['sort_order'] = $this->sortOrder;
        }

        return $attributes;
    }
}
