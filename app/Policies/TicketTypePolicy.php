<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Identity\Models\User;

class TicketTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.ticket-types.view');
    }

    public function view(User $user, TicketType $ticketType): bool
    {
        return $user->can('catalog.ticket-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.ticket-types.create');
    }

    public function update(User $user, TicketType $ticketType): bool
    {
        return $user->can('catalog.ticket-types.update');
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        return $user->can('catalog.ticket-types.delete');
    }
}
