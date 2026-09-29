<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\EventData;
use App\Domain\Catalog\Models\Event;

final class UpdateEventAction
{
    public function handle(Event $event, EventData $data): Event
    {
        $event->update($data->attributes());

        return $event->refresh();
    }
}
