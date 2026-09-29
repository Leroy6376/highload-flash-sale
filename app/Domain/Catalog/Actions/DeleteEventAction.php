<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use App\Domain\Shared\Images\Actions\DeleteImageAction;
use Illuminate\Support\Facades\DB;

final readonly class DeleteEventAction
{
    public function __construct(
        private DeleteImageAction $deleteImage,
        private DeleteTicketTypeAction $deleteTicketType,
    ) {}

    public function handle(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            foreach ($event->images()->get() as $image) {
                $this->deleteImage->handle($image);
            }

            /** @var TicketType $ticketType */
            foreach ($event->ticketTypes()->get() as $ticketType) {
                $this->deleteTicketType->handle($ticketType);
            }

            $event->delete();
        });
    }
}
