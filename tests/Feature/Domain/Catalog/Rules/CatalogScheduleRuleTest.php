<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Catalog\Rules;

use App\Domain\Catalog\Exceptions\CatalogConstraintViolation;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Rules\CatalogScheduleRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogScheduleRuleTest extends TestCase
{
    #[Test]
    #[DataProvider('invalidEventSchedules')]
    public function event_schedule_rejects_invalid_boundaries(?string $endsAt, ?string $salesStartsAt, ?string $salesEndsAt, string $message): void
    {
        $this->expectException(CatalogConstraintViolation::class);
        $this->expectExceptionMessage($message);

        (new CatalogScheduleRule())->assertEvent('2026-10-12 12:00:00', $endsAt, $salesStartsAt, $salesEndsAt);
    }

    #[Test]
    #[DataProvider('invalidTicketSchedules')]
    public function ticket_schedule_rejects_invalid_boundaries(int $capacity, ?int $limit, ?string $startsAt, ?string $endsAt, string $message): void
    {
        $event = new Event([
            'ends_at' => '2026-10-12 18:00:00',
            'sales_ends_at' => '2026-10-12 17:00:00',
            'sales_starts_at' => '2026-10-12 10:00:00',
        ]);
        $this->expectException(CatalogConstraintViolation::class);
        $this->expectExceptionMessage($message);

        (new CatalogScheduleRule())->assertTicketType($event, $capacity, $limit, $startsAt, $endsAt);
    }

    /**
     * @return array<string, array{
     *     0: ?string,
     *     1: ?string,
     *     2: ?string,
     *     3: string,
     * }>
     */
    public static function invalidEventSchedules(): array
    {
        return [
            'event ends at start' => [
                '2026-10-12 12:00:00',
                null,
                null,
                'The event end must be after its start.',
            ],
            'sales end at start' => [
                null,
                '2026-10-12 12:00:00',
                '2026-10-12 12:00:00',
                'The sale end must be after its start.',
            ],
            'sales end after event' => [
                '2026-10-12 13:00:00',
                null,
                '2026-10-12 13:00:01',
                'Ticket sales cannot end after the event ends.',
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     0: int,
     *     1: ?int,
     *     2: ?string,
     *     3: ?string,
     *     4: string,
     * }>
     */
    public static function invalidTicketSchedules(): array
    {
        return [
            'limit exceeds capacity' => [
                2,
                3,
                null,
                null,
                'The sales limit per user cannot exceed capacity.',
            ],
            'sales end at start' => [
                3,
                null,
                '2026-10-12 11:00:00',
                '2026-10-12 11:00:00',
                'The ticket sale end must be after its start.',
            ],
            'sales start before event sales' => [
                3,
                null,
                '2026-10-12 09:59:59',
                null,
                'Ticket sales cannot start before event sales.',
            ],
            'sales end after event sales' => [
                3,
                null,
                null,
                '2026-10-12 17:00:01',
                'Ticket sales cannot end after event sales.',
            ],
        ];
    }
}
