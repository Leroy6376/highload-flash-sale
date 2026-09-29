<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;

final class ShowTicketTypeAction
{
    public function handle(Event $event, TicketType $ticketType): TicketType
    {
        $event = Event::published()->whereKey($event)->firstOrFail();

        return TicketType::active()
            ->whereBelongsTo($event)
            ->whereKey($ticketType)
            ->with('images')
            ->firstOrFail();
    }
}
