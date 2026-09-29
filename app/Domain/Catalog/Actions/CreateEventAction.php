<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\EventData;
use App\Domain\Catalog\Models\Event;

final class CreateEventAction
{
    public function handle(EventData $data): Event
    {
        return Event::create($data->attributes());
    }
}
