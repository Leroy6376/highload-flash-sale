<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Catalog\Data\TicketTypeData;
use App\Domain\Catalog\Enums\Currency;
use App\Domain\Catalog\Enums\TicketTypeStatus;
use App\Domain\Catalog\Models\Event;
use App\Domain\Catalog\Models\TicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketTypeRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $ticketType = $this->route('ticketType');
        /** @var Event|null $event */
        $event = $this->route('event');

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique(TicketType::class, 'slug')->where('event_id', $event?->id)->ignore($ticketType),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'capacity' => ['required', 'integer', 'min:1'],
            'sales_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'sales_starts_at' => ['nullable', 'date'],
            'sales_ends_at' => ['nullable', 'date', 'after:sales_starts_at'],
            'status' => ['required', Rule::enum(TicketTypeStatus::class)],
        ];
    }

    public function ticketTypeData(): TicketTypeData
    {
        /**
         * @var array{
         *     slug: string,
         *     name: string,
         *     description?: string|null,
         *     price_amount: int,
         *     currency: string,
         *     capacity: int,
         *     sales_limit_per_user?: int|null,
         *     sales_starts_at?: string|null,
         *     sales_ends_at?: string|null,
         *     status: string,
         * } $validated
         */
        $validated = $this->validated();

        return new TicketTypeData(
            slug: $validated['slug'],
            name: $validated['name'],
            description: $validated['description'] ?? null,
            priceAmount: $validated['price_amount'],
            currency: Currency::from($validated['currency']),
            capacity: $validated['capacity'],
            salesLimitPerUser: $validated['sales_limit_per_user'] ?? null,
            salesStartsAt: $validated['sales_starts_at'] ?? null,
            salesEndsAt: $validated['sales_ends_at'] ?? null,
            status: TicketTypeStatus::from($validated['status']),
        );
    }
}
