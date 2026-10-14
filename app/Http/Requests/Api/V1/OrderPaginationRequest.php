<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class OrderPaginationRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.config()->integer('orders.pagination.max_per_page'),
            ],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', config()->integer('orders.pagination.default_per_page'));
    }
}
