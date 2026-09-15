<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
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
    public function index(CatalogPaginationRequest $request): AnonymousResourceCollection
    {
        return EventResource::collection(
            Event::published()
                ->chronological()
                ->with('images')
                ->paginate($request->perPage())
                ->withQueryString(),
        );
    }

    #[Unauthenticated]
    public function show(Event $event): EventResource
    {
        $event = Event::published()
            ->whereKey($event)
            ->with('images')
            ->firstOrFail();
        $event->setRelation(
            'ticketTypes',
            TicketType::active()
                ->whereBelongsTo($event)
                ->with('images')
                ->get(),
        );

        return EventResource::make($event);
    }

    #[Authenticated]
    public function store(EventRequest $request): JsonResponse
    {
        $this->authorize('create', Event::class);

        return EventResource::make(Event::create($request->validated()))->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function update(EventRequest $request, Event $event): EventResource
    {
        $this->authorize('update', $event);
        $event->update($request->validated());

        return EventResource::make($event->refresh());
    }

    #[Authenticated]
    public function destroy(Event $event): \Illuminate\Http\Response
    {
        $this->authorize('delete', $event);
        $event->delete();

        return response()->noContent();
    }
}
