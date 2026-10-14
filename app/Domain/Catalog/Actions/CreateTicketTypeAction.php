<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\TicketTypeData;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Catalog\Rules\CatalogScheduleRule;

final readonly class CreateTicketTypeAction
{
    public function __construct(private CatalogScheduleRule $catalogSchedule) {}

    public function handle(Event|string $event, TicketTypeData $data): TicketType
    {
        if (is_string($event)) {
            $event = Event::query()->findOrFail($event);
        }

        $this->catalogSchedule->assertTicketType(
            $event,
            $data->capacity,
            $data->salesLimitPerUser,
            $data->salesStartsAt,
            $data->salesEndsAt,
        );

        return $event->ticketTypes()->create($data->attributes());
    }
}
