<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

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

    /**
     * @return array{
     *     collection?: ImageCollection,
     *     alt_text?: string|null,
     *     sort_order?: int,
     * }
     */
    public function imageAttributes(): array
    {
        $validated = $this->validated();
        $attributes = [];

        if (isset($validated['collection']) && is_string($validated['collection'])) {
            $attributes['collection'] = ImageCollection::from($validated['collection']);
        }

        if (array_key_exists('alt_text', $validated) && (is_string($validated['alt_text']) || $validated['alt_text'] === null)) {
            $attributes['alt_text'] = $validated['alt_text'];
        }

        if (isset($validated['sort_order']) && is_numeric($validated['sort_order'])) {
            $attributes['sort_order'] = (int) $validated['sort_order'];
        }

        return $attributes;
    }

    /**
     * @return array{
     *     collection: ImageCollection,
     *     alt_text?: string|null,
     *     sort_order?: int,
     * }
     */
    public function imageAttributesForCreation(): array
    {
        $attributes = $this->imageAttributes();
        abort_unless(isset($attributes['collection']), 422);

        return $attributes;
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
