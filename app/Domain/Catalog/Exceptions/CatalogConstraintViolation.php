<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class CatalogConstraintViolation extends RuntimeException implements ShouldntReport {}
