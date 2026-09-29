<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\CreateEventAction;
use App\Domain\Catalog\Actions\DeleteEventAction;
use App\Domain\Catalog\Actions\ListEventsAction;
use App\Domain\Catalog\Actions\ShowEventAction;
use App\Domain\Catalog\Actions\UpdateEventAction;
use App\Domain\Catalog\Models\Event;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CatalogPaginationRequest;
use App\Http\Requests\Api\V1\EventRequest;
use App\Http\Resources\Api\V1\EventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\Unauthenticated;

#[Group('Catalog')]
#[Subgroup('Events')]
class EventController extends Controller
{
    public function index(CatalogPaginationRequest $request, ListEventsAction $listEvents): AnonymousResourceCollection
    {
        return EventResource::collection($listEvents->handle($request->perPage()));
    }

    #[Unauthenticated]
    public function show(Event $event, ShowEventAction $showEvent): EventResource
    {
        return EventResource::make($showEvent->handle($event));
    }

    #[Authenticated]
    public function store(EventRequest $request, CreateEventAction $createEvent): JsonResponse
    {
        $this->authorize('create', Event::class);

        return EventResource::make($createEvent->handle($request->eventData()))->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function update(EventRequest $request, Event $event, UpdateEventAction $updateEvent): EventResource
    {
        $this->authorize('update', $event);

        return EventResource::make($updateEvent->handle($event, $request->eventData()));
    }

    #[Authenticated]
    public function destroy(Event $event, DeleteEventAction $deleteEvent): \Illuminate\Http\Response
    {
        $this->authorize('delete', $event);
        $deleteEvent->handle($event);

        return response()->noContent();
    }
}
