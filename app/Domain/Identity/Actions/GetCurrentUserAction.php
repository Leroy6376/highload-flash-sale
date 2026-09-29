<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;

final class GetCurrentUserAction
{
    public function handle(User $user): User
    {
        return $user;
    }
}
