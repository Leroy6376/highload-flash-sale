<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Domain\Catalog\Data\EventData;
use App\Domain\Catalog\Enums\EventStatus;
use App\Domain\Catalog\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Event::class, 'slug')->ignore($event),
            ],
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'timezone' => ['required', 'timezone'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sales_starts_at' => ['nullable', 'date'],
            'sales_ends_at' => ['nullable', 'date', 'after:sales_starts_at'],
            'status' => ['required', Rule::enum(EventStatus::class)],
        ];
    }

    public function eventData(): EventData
    {
        /**
         * @var array{
         *     slug: string,
         *     title: string,
         *     short_description?: string|null,
         *     description?: string|null,
         *     timezone: string,
         *     starts_at: string,
         *     ends_at?: string|null,
         *     sales_starts_at?: string|null,
         *     sales_ends_at?: string|null,
         *     status: string,
         * } $validated
         */
        $validated = $this->validated();

        return new EventData(
            slug: $validated['slug'],
            title: $validated['title'],
            shortDescription: $validated['short_description'] ?? null,
            description: $validated['description'] ?? null,
            timezone: $validated['timezone'],
            startsAt: $validated['starts_at'],
            endsAt: $validated['ends_at'] ?? null,
            salesStartsAt: $validated['sales_starts_at'] ?? null,
            salesEndsAt: $validated['sales_ends_at'] ?? null,
            status: EventStatus::from($validated['status']),
        );
    }
}
