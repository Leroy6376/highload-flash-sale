<?php

declare(strict_types=1);

namespace App\Domain\Shared\Images\Actions;

use App\Domain\Shared\Images\Models\Image;

final class DeleteImageAction
{
    public function handle(Image $image): void
    {
        $image->delete();
    }
}
