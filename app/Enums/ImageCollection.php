<?php

declare(strict_types=1);

namespace App\Enums;

enum ImageCollection: string
{
    case Announcement = 'announcement';
    case Gallery = 'gallery';
}
