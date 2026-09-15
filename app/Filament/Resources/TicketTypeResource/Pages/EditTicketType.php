<?php

declare(strict_types=1);

namespace App\Filament\Resources\TicketTypeResource\Pages;

use App\Filament\Resources\TicketTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditTicketType extends EditRecord
{
    #[\Override]
    protected static string $resource = TicketTypeResource::class;
}
