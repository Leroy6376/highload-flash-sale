<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\TicketType;
use Illuminate\Database\Eloquent\Builder;

final class TicketTypeAdminQueryAction
{
    /** @return Builder<TicketType> */
    public function handle(): Builder
    {
        return TicketType::query();
    }
}
