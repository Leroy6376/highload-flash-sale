<?php

declare(strict_types=1);

namespace App\Domain\Orders\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class OrderConflictException extends RuntimeException implements ShouldntReport {}
