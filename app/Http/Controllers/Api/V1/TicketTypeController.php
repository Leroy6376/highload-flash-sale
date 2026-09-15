<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CatalogPaginationRequest;
use App\Http\Requests\Api\V1\TicketTypeRequest;
use App\Http\Resources\Api\V1\TicketTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\Unauthenticated;

#[Group('Catalog')]
#[Subgroup('Ticket types')]
class TicketTypeController extends Controller
{
    public function index(CatalogPaginationRequest $request, Event $event): AnonymousResourceCollection
    {
        $event = Event::published()->whereKey($event)->firstOrFail();

        return TicketTypeResource::collection(
            TicketType::active()
                ->whereBelongsTo($event)
                ->with('images')
                ->paginate($request->perPage())
                ->withQueryString(),
        );
    }

    #[Unauthenticated]
    public function show(Event $event, TicketType $ticketType): TicketTypeResource
    {
        $event = Event::published()->whereKey($event)->firstOrFail();
        $ticketType = TicketType::active()
            ->whereBelongsTo($event)
            ->whereKey($ticketType)
            ->with('images')
            ->firstOrFail();

        return TicketTypeResource::make($ticketType);
    }

    #[Authenticated]
    public function store(TicketTypeRequest $request, Event $event): JsonResponse
    {
        $this->authorize('create', TicketType::class);

        return TicketTypeResource::make($event->ticketTypes()->create($request->validated()))->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function update(TicketTypeRequest $request, Event $event, TicketType $ticketType): TicketTypeResource
    {
        $this->authorize('update', $ticketType);
        $ticketType->update($request->validated());

        return TicketTypeResource::make($ticketType->refresh());
    }

    #[Authenticated]
    public function destroy(Event $event, TicketType $ticketType): \Illuminate\Http\Response
    {
        $this->authorize('delete', $ticketType);
        $ticketType->delete();

        return response()->noContent();
    }
}
