<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;

interface PendingAllocationInvalidator
{
    public function expireForEvent(Event $event): void;

    public function expireForTicketType(TicketType $ticketType): void;
}
