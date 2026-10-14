<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;

interface TicketAllocationReader
{
    public function allocatedQuantity(TicketType $ticketType): int;

    public function hasOrderHistoryForEvent(Event $event): bool;

    public function hasOrderHistoryForTicketType(TicketType $ticketType): bool;
}
