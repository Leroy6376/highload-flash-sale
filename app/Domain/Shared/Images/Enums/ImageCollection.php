<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Enums;

enum ImageCollection: string
{
    case Announcement = 'announcement';
    case Gallery = 'gallery';
}
