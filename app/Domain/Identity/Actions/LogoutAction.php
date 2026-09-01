<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutAction
{
    public function handle(User $user): void
    {
        $accessToken = $user->currentAccessToken();
        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }
    }
}
