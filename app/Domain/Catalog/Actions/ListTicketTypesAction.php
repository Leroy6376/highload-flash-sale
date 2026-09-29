<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTicketTypesAction
{
    /** @return LengthAwarePaginator<int, TicketType> */
    public function handle(Event $event, int $perPage): LengthAwarePaginator
    {
        $event = Event::published()->whereKey($event)->firstOrFail();

        return TicketType::active()
            ->whereBelongsTo($event)
            ->with('images')
            ->paginate($perPage)
            ->withQueryString();
    }
}
