<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Actions;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Data\CreateImageData;
use App\Domain\Shared\Images\Models\Image;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class CreateImageAction
{
    public function handle(Event|TicketType $imageable, CreateImageData $data): Image
    {
        $path = $data->file->store('catalog/'.class_basename($imageable).'/'.$imageable->id, 'public');

        if ($path === false) {
            throw new RuntimeException('Unable to store the image file.');
        }

        try {
            return $imageable->images()->create([
                ...$data->attributes(),
                'path' => $path,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }
    }
}
