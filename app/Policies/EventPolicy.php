<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Catalog\Models\Event;
use App\Domain\Identity\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return $user->can('catalog.events.view');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.events.create');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can('catalog.events.update');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can('catalog.events.delete');
    }
}
