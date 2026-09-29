<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Actions;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Data\UpdateImageData;
use App\Domain\Shared\Images\Models\Image;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class UpdateImageAction
{
    public function handle(Event|TicketType $imageable, Image $image, UpdateImageData $data): Image
    {
        $attributes = $data->attributes();
        $newPath = null;
        $oldPath = $image->path;

        if ($data->file !== null) {
            $newPath = $data->file->store('catalog/'.class_basename($imageable).'/'.$imageable->id, 'public');

            if ($newPath === false) {
                throw new RuntimeException('Unable to store the image file.');
            }

            $attributes['path'] = $newPath;
        }

        try {
            $image->update($attributes);
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }

        return $image->refresh();
    }
}
