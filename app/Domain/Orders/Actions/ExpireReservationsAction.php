<?php

declare(strict_types=1);

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Services\TicketAllocationService;

final readonly class ExpireReservationsAction
{
    public function __construct(private TicketAllocationService $ticketAllocation) {}

    public function handle(): void
    {
        $this->ticketAllocation->expireStaleReservations();
    }
}
