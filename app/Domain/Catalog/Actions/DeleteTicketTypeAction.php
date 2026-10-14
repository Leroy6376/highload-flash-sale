<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Contracts\TicketAllocationReader;
use App\Domain\Catalog\Exceptions\CatalogConstraintViolation;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Actions\DeleteImageAction;
use Illuminate\Support\Facades\DB;

final readonly class DeleteTicketTypeAction
{
    public function __construct(
        private DeleteImageAction $deleteImage,
        private TicketAllocationReader $ticketAllocationReader,
    ) {}

    public function handle(TicketType $ticketType): void
    {
        DB::transaction(function () use ($ticketType): void {
            if ($this->ticketAllocationReader->hasOrderHistoryForTicketType($ticketType)) {
                throw new CatalogConstraintViolation('Ticket types with order history cannot be deleted.');
            }
            foreach ($ticketType->images()->get() as $image) {
                $this->deleteImage->handle($image);
            }

            $ticketType->delete();
        });
    }
}
