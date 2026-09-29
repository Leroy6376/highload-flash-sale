<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;

final readonly class TicketTypeData
{
    public function __construct(
        public string $slug,
        public string $name,
        public ?string $description,
        public int $priceAmount,
        public Currency $currency,
        public int $capacity,
        public ?int $salesLimitPerUser,
        public ?string $salesStartsAt,
        public ?string $salesEndsAt,
        public TicketTypeStatus $status,
        public ?string $eventId = null,
    ) {}

    /** @return array<string, string|int|null|Currency|TicketTypeStatus> */
    public function attributes(): array
    {
        $attributes = [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'price_amount' => $this->priceAmount,
            'currency' => $this->currency,
            'capacity' => $this->capacity,
            'sales_limit_per_user' => $this->salesLimitPerUser,
            'sales_starts_at' => $this->salesStartsAt,
            'sales_ends_at' => $this->salesEndsAt,
            'status' => $this->status,
        ];

        if ($this->eventId !== null) {
            $attributes['event_id'] = $this->eventId;
        }

        return $attributes;
    }
}
