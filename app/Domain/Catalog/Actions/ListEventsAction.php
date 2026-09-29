<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListEventsAction
{
    /** @return LengthAwarePaginator<int, Event> */
    public function handle(int $perPage): LengthAwarePaginator
    {
        return Event::published()
            ->chronological()
            ->with('images')
            ->paginate($perPage)
            ->withQueryString();
    }
}
