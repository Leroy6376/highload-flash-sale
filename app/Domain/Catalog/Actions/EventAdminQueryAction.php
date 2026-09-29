<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Event;
use Illuminate\Database\Eloquent\Builder;

final class EventAdminQueryAction
{
    /** @return Builder<Event> */
    public function handle(): Builder
    {
        return Event::query();
    }
}
