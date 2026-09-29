<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Data;

use App\Domain\Catalog\Enums\EventStatus;

final readonly class EventData
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $shortDescription,
        public ?string $description,
        public string $timezone,
        public string $startsAt,
        public ?string $endsAt,
        public ?string $salesStartsAt,
        public ?string $salesEndsAt,
        public EventStatus $status,
    ) {}

    /** @return array<string, string|null|EventStatus> */
    public function attributes(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'timezone' => $this->timezone,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'sales_starts_at' => $this->salesStartsAt,
            'sales_ends_at' => $this->salesEndsAt,
            'status' => $this->status,
        ];
    }
}
