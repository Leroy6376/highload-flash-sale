<?php

declare(strict_types=1);

namespace App\Filament\Resources\TicketTypeResource\Pages;

use App\Filament\Resources\TicketTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketType extends CreateRecord
{
    #[\Override]
    protected static string $resource = TicketTypeResource::class;
}
