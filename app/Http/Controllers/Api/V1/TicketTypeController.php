<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\CreateTicketTypeAction;
use App\Domain\Catalog\Actions\DeleteTicketTypeAction;
use App\Domain\Catalog\Actions\ListTicketTypesAction;
use App\Domain\Catalog\Actions\ShowTicketTypeAction;
use App\Domain\Catalog\Actions\UpdateTicketTypeAction;
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
    public function index(CatalogPaginationRequest $request, Event $event, ListTicketTypesAction $listTicketTypes): AnonymousResourceCollection
    {
        return TicketTypeResource::collection($listTicketTypes->handle($event, $request->perPage()));
    }

    #[Unauthenticated]
    public function show(Event $event, TicketType $ticketType, ShowTicketTypeAction $showTicketType): TicketTypeResource
    {
        return TicketTypeResource::make($showTicketType->handle($event, $ticketType));
    }

    #[Authenticated]
    public function store(TicketTypeRequest $request, Event $event, CreateTicketTypeAction $createTicketType): JsonResponse
    {
        $this->authorize('create', TicketType::class);

        return TicketTypeResource::make($createTicketType->handle($event, $request->ticketTypeData()))->response()->setStatusCode(201);
    }

    #[Authenticated]
    public function update(TicketTypeRequest $request, Event $event, TicketType $ticketType, UpdateTicketTypeAction $updateTicketType): TicketTypeResource
    {
        $this->authorize('update', $ticketType);

        return TicketTypeResource::make($updateTicketType->handle($ticketType, $request->ticketTypeData()));
    }

    #[Authenticated]
    public function destroy(Event $event, TicketType $ticketType, DeleteTicketTypeAction $deleteTicketType): \Illuminate\Http\Response
    {
        $this->authorize('delete', $ticketType);
        $deleteTicketType->handle($ticketType);

        return response()->noContent();
    }
}
