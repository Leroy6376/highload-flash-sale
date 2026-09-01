<?php

declare(strict_types=1);

namespace App\Http\Documentation;

final class ProblemDetailsExamples
{
    public const array Unauthorized = [
        'type' => 'about:blank',
        'title' => 'Unauthorized',
        'status' => 401,
        'detail' => 'Authentication failed.',
        'instance' => '/api/v1/auth/me',
        'request_id' => '01JEXAMPLE0000000000000000',
    ];

    public const array Validation = [
        'type' => 'about:blank',
        'title' => 'Unprocessable Content',
        'status' => 422,
        'detail' => 'The request contains invalid fields.',
        'instance' => '/api/v1/auth/login',
        'request_id' => '01JEXAMPLE0000000000000000',
        'errors' => [
            [
                'detail' => 'The email field is required.',
                'pointer' => '#/email',
            ],
        ],
    ];

    public const array NotFound = [
        'type' => 'about:blank',
        'title' => 'Not Found',
        'status' => 404,
        'detail' => 'The requested resource was not found.',
        'instance' => '/api/v1/auth/tokens/1',
        'request_id' => '01JEXAMPLE0000000000000000',
    ];
}
