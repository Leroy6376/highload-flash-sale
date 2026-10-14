<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Rules;

use App\Domain\Catalog\Exceptions\CatalogConstraintViolation;
use App\Domain\Catalog\Models\Event;
use Carbon\CarbonImmutable;

final class CatalogScheduleRule
{
    public function assertEvent(string $startsAt, ?string $endsAt, ?string $salesStartsAt, ?string $salesEndsAt): void
    {
        $startsAt = CarbonImmutable::parse($startsAt);
        $endsAt = $this->parseNullable($endsAt);
        $salesStartsAt = $this->parseNullable($salesStartsAt);
        $salesEndsAt = $this->parseNullable($salesEndsAt);

        if ($endsAt !== null && $endsAt <= $startsAt) {
            throw new CatalogConstraintViolation('The event end must be after its start.');
        }
        if ($salesStartsAt !== null && $salesEndsAt !== null && $salesEndsAt <= $salesStartsAt) {
            throw new CatalogConstraintViolation('The sale end must be after its start.');
        }
        if ($endsAt !== null && $salesEndsAt !== null && $salesEndsAt > $endsAt) {
            throw new CatalogConstraintViolation('Ticket sales cannot end after the event ends.');
        }
    }

    public function assertTicketType(
        Event $event,
        int $capacity,
        ?int $salesLimitPerUser,
        ?string $salesStartsAt,
        ?string $salesEndsAt,
    ): void {
        if ($salesLimitPerUser !== null && $salesLimitPerUser > $capacity) {
            throw new CatalogConstraintViolation('The sales limit per user cannot exceed capacity.');
        }

        $salesStartsAt = $this->parseNullable($salesStartsAt);
        $salesEndsAt = $this->parseNullable($salesEndsAt);
        if ($salesStartsAt !== null && $salesEndsAt !== null && $salesEndsAt <= $salesStartsAt) {
            throw new CatalogConstraintViolation('The ticket sale end must be after its start.');
        }
        if ($event->sales_starts_at !== null && $salesStartsAt !== null && $salesStartsAt < $event->sales_starts_at) {
            throw new CatalogConstraintViolation('Ticket sales cannot start before event sales.');
        }
        if ($event->sales_ends_at !== null && $salesEndsAt !== null && $salesEndsAt > $event->sales_ends_at) {
            throw new CatalogConstraintViolation('Ticket sales cannot end after event sales.');
        }
        if ($event->ends_at !== null && $salesEndsAt !== null && $salesEndsAt > $event->ends_at) {
            throw new CatalogConstraintViolation('Ticket sales cannot end after the event ends.');
        }
    }

    private function parseNullable(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value);
    }
}
