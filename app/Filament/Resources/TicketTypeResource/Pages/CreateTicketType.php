<?php

declare(strict_types=1);

namespace App\Filament\Resources\TicketTypeResource\Pages;

use App\Domain\Catalog\Actions\CreateTicketTypeAction;
use App\Filament\Resources\TicketTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateTicketType extends CreateRecord
{
    #[\Override]
    protected static string $resource = TicketTypeResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $eventId = $data['event_id'] ?? null;

        if (! is_string($eventId)) {
            throw new InvalidArgumentException('The event_id field must be a string.');
        }

        return resolve(CreateTicketTypeAction::class)->handle(
            $eventId,
            TicketTypeResource::ticketTypeData($data),
        );
    }
}
