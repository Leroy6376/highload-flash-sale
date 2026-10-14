<?php

declare(strict_types=1);

namespace App\Domain\Identity\Data;

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $deviceName,
    ) {}
}
