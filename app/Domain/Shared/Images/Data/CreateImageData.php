<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Data;

use App\Domain\Shared\Images\Enums\ImageCollection;
use Illuminate\Http\UploadedFile;

final readonly class CreateImageData
{
    public function __construct(
        public UploadedFile $file,
        public ImageCollection $collection,
        public ?string $altText,
        public int $sortOrder,
    ) {}

    /**
     * @return array{
     *     collection: ImageCollection,
     *     alt_text: string|null,
     *     sort_order: int
     * }
     */
    public function attributes(): array
    {
        return [
            'collection' => $this->collection,
            'alt_text' => $this->altText,
            'sort_order' => $this->sortOrder,
        ];
    }
}
