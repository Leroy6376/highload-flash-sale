<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

enum ImageCollection: string
{
    case Announcement = 'announcement';
    case Gallery = 'gallery';
}
