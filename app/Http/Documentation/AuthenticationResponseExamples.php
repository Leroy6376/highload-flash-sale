<?php

declare(strict_types=1);

namespace App\Http\Documentation;

final class AuthenticationResponseExamples
{
    public const array Registration = [
        'token' => '1|example-access-token',
        'token_type' => 'Bearer',
        'expires_at' => '2026-10-12T12:15:00.000000Z',
        'user' => [
            'id' => 1,
            'name' => 'Иван Петров',
            'email' => 'ivan.petrov@example.test',
            'roles' => ['customer'],
        ],
    ];
}
