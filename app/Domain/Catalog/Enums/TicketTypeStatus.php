<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum TicketTypeStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Disabled = 'disabled';
}
