<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Domain\Catalog\Actions\UpdateEventAction;
use App\Domain\Catalog\Models\Event;
use App\Filament\Resources\EventResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEvent extends EditRecord
{
    #[\Override]
    protected static string $resource = EventResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Event);

        return resolve(UpdateEventAction::class)->handle($record, EventResource::eventData($data));
    }
}
