<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\TicketTypeData;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;

final class CreateTicketTypeAction
{
    public function handle(Event|string $event, TicketTypeData $data): TicketType
    {
        if (is_string($event)) {
            $event = Event::query()->findOrFail($event);
        }

        return $event->ticketTypes()->create($data->attributes());
    }
}
