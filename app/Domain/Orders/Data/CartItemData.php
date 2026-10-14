<?php

declare(strict_types=1);

namespace App\Domain\Orders\Data;

final readonly class CartItemData
{
    public function __construct(
        public string $ticketTypeId,
        public int $quantity,
    ) {}
}
