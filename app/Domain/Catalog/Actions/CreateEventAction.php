<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\EventData;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Rules\CatalogScheduleRule;

final readonly class CreateEventAction
{
    public function __construct(private CatalogScheduleRule $catalogSchedule) {}

    public function handle(EventData $data): Event
    {
        $this->catalogSchedule->assertEvent($data->startsAt, $data->endsAt, $data->salesStartsAt, $data->salesEndsAt);

        return Event::create($data->attributes());
    }
}
