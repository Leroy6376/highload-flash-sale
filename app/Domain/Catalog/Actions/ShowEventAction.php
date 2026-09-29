<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;

final class ShowEventAction
{
    public function handle(Event $event): Event
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

        return $event;
    }
}
