<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Models\Image;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImageRequest;
use App\Http\Resources\Api\V1\ImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;

#[Group('Catalog')]
#[Subgroup('Images')]
class CatalogImageController extends Controller
{
    public function store(ImageRequest $request, Event $event, ?TicketType $ticketType = null): JsonResponse
    {
        $imageable = $this->imageable($event, $ticketType);
        $file = $request->uploadedFile();
        $data = $request->imageAttributesForCreation();
        $data['path'] = $file->store('catalog/'.class_basename($imageable).'/'.$imageable->id, 'public');

        return ImageResource::make($imageable->images()->create($data))->response()->setStatusCode(201);
    }

    public function update(ImageRequest $request, Event $event, Image $image, ?TicketType $ticketType = null): ImageResource
    {
        $imageable = $this->imageable($event, $ticketType);
        $data = $request->imageAttributes();
        if ($request->hasFile('file')) {
            $oldPath = $image->path;
            $file = $request->uploadedFile();
            $data['path'] = $file->store('catalog/'.class_basename($imageable).'/'.$imageable->id, 'public');
            Storage::disk('public')->delete($oldPath);
        }
        $image->update($data);

        return ImageResource::make($image->refresh());
    }

    public function destroy(Event $event, Image $image, ?TicketType $ticketType = null): \Illuminate\Http\Response
    {
        $image->delete();

        return response()->noContent();
    }

    private function imageable(Event $event, ?TicketType $ticketType): Event|TicketType
    {
        return $ticketType ?? $event;
    }
}
