<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\PendingAllocationInvalidator;
use App\Domain\Catalog\Data\EventData;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Rules\CatalogScheduleRule;
use Illuminate\Support\Facades\DB;

final readonly class UpdateEventAction
{
    public function __construct(
        private CatalogScheduleRule $catalogSchedule,
        private PendingAllocationInvalidator $pendingAllocationInvalidator,
    ) {}

    public function handle(Event $event, EventData $data): Event
    {
        return DB::transaction(function () use ($event, $data): Event {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            $this->catalogSchedule->assertEvent($data->startsAt, $data->endsAt, $data->salesStartsAt, $data->salesEndsAt);
            $event->update($data->attributes());
            if ($data->status === EventStatus::Cancelled) {
                $event->ticketTypes()->orderBy('id')->lockForUpdate()->get();
                $this->pendingAllocationInvalidator->expireForEvent($event);
            }

            return $event->refresh();
        });
    }
}
