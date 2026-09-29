<?php

declare(strict_types=1);

namespace App\Filament\Resources\TicketTypeResource\Pages;

use App\Domain\Catalog\Actions\UpdateTicketTypeAction;
use App\Domain\Catalog\Models\TicketType;
use App\Filament\Resources\TicketTypeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTicketType extends EditRecord
{
    #[\Override]
    protected static string $resource = TicketTypeResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof TicketType);

        return resolve(UpdateTicketTypeAction::class)->handle($record, TicketTypeResource::ticketTypeData($data));
    }
}
