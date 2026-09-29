<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Data\TicketTypeData;
use App\Domain\Catalog\Models\TicketType;

final class UpdateTicketTypeAction
{
    public function handle(TicketType $ticketType, TicketTypeData $data): TicketType
    {
        $ticketType->update($data->attributes());

        return $ticketType->refresh();
    }
}
