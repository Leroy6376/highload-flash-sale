<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Shared\Images\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ImageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Image $image */
        $image = $this->resource;

        return [
            'id' => $image->id,
            'collection' => $image->collection->value,
            'url' => Storage::disk('public')->url($image->path),
            'alt_text' => $image->alt_text,
            'sort_order' => $image->sort_order,
        ];
    }
}
