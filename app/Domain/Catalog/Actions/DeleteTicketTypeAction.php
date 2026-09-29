<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Actions\DeleteImageAction;
use Illuminate\Support\Facades\DB;

final readonly class DeleteTicketTypeAction
{
    public function __construct(private DeleteImageAction $deleteImage) {}

    public function handle(TicketType $ticketType): void
    {
        DB::transaction(function () use ($ticketType): void {
            foreach ($ticketType->images()->get() as $image) {
                $this->deleteImage->handle($image);
            }

            $ticketType->delete();
        });
    }
}
