<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\PendingAllocationInvalidator;
use App\Domain\Catalog\Contracts\TicketAllocationReader;
use App\Domain\Catalog\Data\TicketTypeData;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Exceptions\CatalogConstraintViolation;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Catalog\Rules\CatalogScheduleRule;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTicketTypeAction
{
    public function __construct(
        private CatalogScheduleRule $catalogSchedule,
        private PendingAllocationInvalidator $pendingAllocationInvalidator,
        private TicketAllocationReader $ticketAllocationReader,
    ) {}

    public function handle(TicketType $ticketType, TicketTypeData $data): TicketType
    {
        return DB::transaction(function () use ($ticketType, $data): TicketType {
            $ticketType = TicketType::query()->with('event')->lockForUpdate()->findOrFail($ticketType->id);
            $event = $data->eventId === null
                ? $ticketType->event()->firstOrFail()
                : Event::query()->lockForUpdate()->findOrFail($data->eventId);
            $this->catalogSchedule->assertTicketType(
                $event,
                $data->capacity,
                $data->salesLimitPerUser,
                $data->salesStartsAt,
                $data->salesEndsAt,
            );
            if ($data->capacity < $this->ticketAllocationReader->allocatedQuantity($ticketType)) {
                throw new CatalogConstraintViolation('Capacity cannot be lower than allocated tickets.');
            }
            $ticketType->update($data->attributes());
            if ($data->status === TicketTypeStatus::Disabled) {
                $this->pendingAllocationInvalidator->expireForTicketType($ticketType);
            }

            return $ticketType->refresh();
        });
    }
}
