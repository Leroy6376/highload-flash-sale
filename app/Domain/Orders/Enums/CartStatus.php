<?php

declare(strict_types=1);

namespace App\Domain\Orders\Enums;

enum CartStatus: string
{
    case Active = 'active';
    case CheckedOut = 'checked_out';
    case Expired = 'expired';
}
