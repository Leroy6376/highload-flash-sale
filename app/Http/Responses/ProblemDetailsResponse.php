<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class ProblemDetailsResponse
{
    /**
     * @param list<array{detail: string, pointer: string}> $errors
     * @param array<string, mixed> $headers
     */
    public static function make(
        Request $request,
        int $status,
        string $detail,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        $response = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getPathInfo(),
            'request_id' => Context::get('request_id'),
        ];

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status, [
            ...$headers,
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
