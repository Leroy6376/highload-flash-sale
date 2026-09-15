<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CatalogPaginationRequest extends FormRequest
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
                'max:'.config()->integer('catalog.pagination.max_per_page'),
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     description: string,
     *     example: int,
     * }>
     */
    public function queryParameters(): array
    {
        return [
            'page' => [
                'description' => 'Номер страницы.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Количество записей на странице (от 1 до '.config()->integer('catalog.pagination.max_per_page').').',
                'example' => config()->integer('catalog.pagination.default_per_page'),
            ],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', config()->integer('catalog.pagination.default_per_page'));
    }
}
