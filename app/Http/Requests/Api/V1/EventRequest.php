<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

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
}
