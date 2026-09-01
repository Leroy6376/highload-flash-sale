<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class RevokePersonalAccessTokenAction
{
    public function handle(User $user, string $tokenId): void
    {
        PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->getKey())
            ->findOrFail($tokenId)
            ->delete();
    }
}
