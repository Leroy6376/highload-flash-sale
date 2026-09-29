<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Shared\Images\Data\CreateImageData;
use App\Domain\Shared\Images\Data\UpdateImageData;
use App\Domain\Shared\Images\Enums\ImageCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImageRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'file' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'collection' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                Rule::enum(ImageCollection::class),
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function createImageData(): CreateImageData
    {
        /**
         * @var array{
         *     collection: string,
         *     alt_text?: string|null,
         *     sort_order?: int
         * } $validated
         */
        $validated = $this->validated();

        return new CreateImageData(
            file: $this->uploadedFile(),
            collection: ImageCollection::from($validated['collection']),
            altText: $validated['alt_text'] ?? null,
            sortOrder: $validated['sort_order'] ?? 0,
        );
    }

    public function updateImageData(): UpdateImageData
    {
        /**
         * @var array{
         *     collection?: string,
         *     alt_text?: string|null,
         *     sort_order?: int
         * } $validated
         */
        $validated = $this->validated();
        $file = $this->file('file');

        return new UpdateImageData(
            file: $file instanceof UploadedFile ? $file : null,
            hasCollection: array_key_exists('collection', $validated),
            collection: isset($validated['collection']) ? ImageCollection::from($validated['collection']) : null,
            hasAltText: array_key_exists('alt_text', $validated),
            altText: $validated['alt_text'] ?? null,
            hasSortOrder: array_key_exists('sort_order', $validated),
            sortOrder: $validated['sort_order'] ?? null,
        );
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => ['The file field is required.'],
            ]);
        }

        return $file;
    }
}
