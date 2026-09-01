<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case CatalogManager = 'catalog-manager';
    case Customer = 'customer';
}
