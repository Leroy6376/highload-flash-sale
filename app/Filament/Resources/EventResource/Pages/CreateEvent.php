<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Domain\Catalog\Actions\CreateEventAction;
use App\Filament\Resources\EventResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEvent extends CreateRecord
{
    #[\Override]
    protected static string $resource = EventResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        return resolve(CreateEventAction::class)->handle(EventResource::eventData($data));
    }
}
