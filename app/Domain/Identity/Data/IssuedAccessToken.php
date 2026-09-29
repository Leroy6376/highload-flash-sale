<?php

declare(strict_types=1);

namespace App\Domain\Identity\Data;

use App\Domain\Identity\Models\User;
use Carbon\CarbonImmutable;

final readonly class IssuedAccessToken
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
        public CarbonImmutable $expiresAt,
    ) {}
}
