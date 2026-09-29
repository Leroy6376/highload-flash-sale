<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Actions\CreateImageAction;
use App\Domain\Shared\Images\Actions\DeleteImageAction;
use App\Domain\Shared\Images\Actions\UpdateImageAction;
use App\Domain\Shared\Images\Models\Image;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImageRequest;
use App\Http\Resources\Api\V1\ImageResource;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;

#[Group('Catalog')]
#[Subgroup('Images')]
class CatalogImageController extends Controller
{
    public function store(ImageRequest $request, Event $event, CreateImageAction $createImage, ?TicketType $ticketType = null): JsonResponse
    {
        $imageable = $this->imageable($event, $ticketType);

        return ImageResource::make($createImage->handle($imageable, $request->createImageData()))->response()->setStatusCode(201);
    }

    public function update(ImageRequest $request, Event $event, Image $image, UpdateImageAction $updateImage, ?TicketType $ticketType = null): ImageResource
    {
        $imageable = $this->imageable($event, $ticketType);

        return ImageResource::make($updateImage->handle($imageable, $image, $request->updateImageData()));
    }

    public function destroy(Event $event, Image $image, DeleteImageAction $deleteImage, ?TicketType $ticketType = null): \Illuminate\Http\Response
    {
        $deleteImage->handle($image);

        return response()->noContent();
    }

    private function imageable(Event $event, ?TicketType $ticketType): Event|TicketType
    {
        return $ticketType ?? $event;
    }
}
